<?php

namespace App\Domain\PlayerAccounts\Services;

use App\Domain\Audit\Data\AuditContext;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Auth\Services\UserDirectory;
use App\Domain\Auth\Services\VerifiedAccountCounter;
use App\Domain\CocIntegration\Data\PlayerData;
use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Domain\CocIntegration\Services\PlayerLookup;
use App\Domain\CocIntegration\Services\TokenVerifier;
use App\Domain\Notifications\Enums\NoticeKind;
use App\Domain\Notifications\Events\NoticeRequested;
use App\Domain\PlayerAccounts\Data\PlayerPreview;
use App\Domain\PlayerAccounts\Data\VerificationResult;
use App\Domain\PlayerAccounts\Enums\AttachError;
use App\Domain\PlayerAccounts\Enums\ClaimFailureReason;
use App\Domain\PlayerAccounts\Enums\ClaimMethod;
use App\Domain\PlayerAccounts\Enums\ClaimStatus;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Enums\DisputeStatus;
use App\Domain\PlayerAccounts\Enums\VerificationMethod;
use App\Domain\PlayerAccounts\Enums\VerificationOutcome;
use App\Domain\PlayerAccounts\Events\CocAccountVerified;
use App\Domain\PlayerAccounts\Exceptions\AccountAttachException;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Models\CocAccountClaim;
use App\Domain\PlayerAccounts\Models\CocAccountDispute;
use App\Support\ValueObjects\PlayerTag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Attach and token-verification (specs/13 §3, §3.1). `preview` confirms the tag exists and surfaces any
 * conflicting verified holder; `verify` runs the authoritative transaction: an in-game token supersedes
 * an existing verified holder because possession of a current token is proof of present control. Ownership
 * transitions are set directly on the model — never mass-assigned — and every attempt writes a claim row.
 */
final class AccountAttachService
{
    public function __construct(
        private readonly PlayerLookup $players,
        private readonly TokenVerifier $tokens,
        private readonly UserDirectory $directory,
        private readonly VerifiedAccountCounter $counter,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * "Is this you?" — validate, rate-limit, reject a duplicate, fetch the player and detect a conflict.
     *
     * @throws AccountAttachException
     */
    public function preview(int $userId, string $rawTag): PlayerPreview
    {
        $tag = $this->tag($rawTag);
        $this->rateLimit("coc-attach:{$userId}", (int) config('coc.attach.attempts_per_hour'));

        $normalized = $this->normalize($tag);

        if ($this->userHolds($userId, $normalized)) {
            throw AccountAttachException::of(AttachError::AlreadyAttached);
        }

        $player = $this->fetchPlayer($tag);
        $holder = $this->verifiedHolder($normalized);
        $conflict = $holder !== null && $holder->user_id !== $userId
            ? $this->directory->usernameById((int) $holder->user_id)
            : null;

        return PlayerPreview::fromPlayer($player, $conflict);
    }

    /**
     * Verify ownership with an in-game token. Returns InvalidToken as an ordinary result; only transport
     * failures and rate limits raise {@see AccountAttachException}.
     *
     * @throws AccountAttachException
     */
    public function verify(int $userId, string $rawTag, string $token, ?AuditContext $context = null): VerificationResult
    {
        $context ??= AuditContext::fromRequest(request());
        $tag = $this->tag($rawTag);
        $normalized = $this->normalize($tag);

        if (RateLimiter::tooManyAttempts("coc-verify:{$userId}", (int) config('coc.attach.verify_attempts_per_hour'))) {
            $this->claim($userId, $normalized, ClaimStatus::Failed, ClaimFailureReason::RateLimited, null, $context);
            throw AccountAttachException::of(AttachError::RateLimited);
        }
        RateLimiter::hit("coc-verify:{$userId}", 3600);

        $player = $this->fetchPlayer($tag);

        try {
            $result = $this->tokens->verify($tag, $token);
        } catch (CocApiException) {
            $this->claim($userId, $normalized, ClaimStatus::Failed, ClaimFailureReason::ApiError, null, $context);
            throw AccountAttachException::of(AttachError::ApiUnavailable);
        }

        if (! $result->ok) {
            $this->claim($userId, $normalized, ClaimStatus::Failed, ClaimFailureReason::InvalidToken, null, $context);

            return new VerificationResult(VerificationOutcome::InvalidToken);
        }

        return DB::transaction(fn (): VerificationResult => $this->promote($userId, $tag, $normalized, $player, $context));
    }

    /** The single authoritative transaction (specs/13 §3.1). Assumes a valid token was just presented. */
    private function promote(int $userId, PlayerTag $tag, string $normalized, PlayerData $player, AuditContext $context): VerificationResult
    {
        $rows = CocAccount::query()->where('tag_normalized', $normalized)->lockForUpdate()->get();

        $superseded = false;
        $previousHolderId = null;
        // A token beats an existing verified holder AND a holder currently under dispute — possession of
        // a live token is proof of present control (specs/13 §3.1, §5 step 3a).
        $holder = $rows->first(fn (CocAccount $r): bool => in_array($r->status, [CocAccountStatus::Verified, CocAccountStatus::Disputed], true) && $r->user_id !== $userId);
        if ($holder !== null) {
            $previousHolderId = (int) $holder->user_id;
            $holder->forceFill([
                'status' => CocAccountStatus::Unverified->value,
                'previous_user_id' => $previousHolderId,
            ])->save();
            CocAccountClaim::query()
                ->where('coc_account_id', $holder->id)
                ->whereIn('status', [ClaimStatus::Pending->value, ClaimStatus::Succeeded->value])
                ->update(['status' => ClaimStatus::Superseded->value]);
            $superseded = true;
        }

        $account = $rows->first(fn (CocAccount $r): bool => $r->user_id === $userId)
            ?? $rows->first(fn (CocAccount $r): bool => $r->status === CocAccountStatus::Released)
            ?? new CocAccount;

        $account->fill($this->syncData($player));
        if (! $account->exists) {
            $account->ulid = (string) Str::ulid();
        }
        $account->forceFill([
            'user_id' => $userId,
            'tag' => $tag->value,
            'tag_normalized' => $normalized,
            'status' => CocAccountStatus::Verified->value,
            'verified_at' => now(),
            'verification_method' => VerificationMethod::ApiToken->value,
            'api_sync_failures' => 0,
        ]);

        $isFirst = CocAccount::query()
            ->where('user_id', $userId)
            ->where('status', CocAccountStatus::Verified->value)
            ->when($account->exists, fn ($q) => $q->where('id', '!=', $account->id))
            ->doesntExist();
        if ($isFirst) {
            $account->forceFill(['is_featured' => true]);
        }

        $account->save();

        $this->counter->increment($userId);
        $this->claim($userId, $normalized, ClaimStatus::Succeeded, null, (int) $account->id, $context);

        $this->audit->record(
            actorId: $userId,
            actorRole: null,
            action: $superseded ? AuditAction::CocAccountSuperseded : AuditAction::CocAccountVerified,
            auditableType: (new CocAccount)->getMorphClass(),
            auditableId: (int) $account->id,
            before: ['user_id' => $previousHolderId],
            after: ['user_id' => $userId],
            context: $context,
        );

        event(new NoticeRequested($userId, NoticeKind::AccountVerified));
        if ($previousHolderId !== null) {
            event(new NoticeRequested($previousHolderId, NoticeKind::AccountSuperseded));
        }
        event(new CocAccountVerified((int) $account->id, $userId, $previousHolderId));

        $this->autoResolveDisputes($normalized, $userId);

        return new VerificationResult(VerificationOutcome::Verified, $superseded, (int) $account->id);
    }

    /**
     * A token verification ends every live dispute on the tag at once (specs/13 §3.1 step 8, §5 step 3a):
     * the claimant who just verified wins (`auto_resolved`); the holder who defended by token beats the
     * claim (`resolved_denied`); a third party's token supersedes both, so the dispute is moot
     * (`auto_resolved`). Both parties are notified of the decision. Runs inside the verify transaction.
     */
    private function autoResolveDisputes(string $normalized, int $verifierId): void
    {
        $disputes = CocAccountDispute::query()
            ->where('tag_normalized', $normalized)
            ->whereIn('status', DisputeStatus::liveValues())
            ->lockForUpdate()
            ->get();

        foreach ($disputes as $dispute) {
            $holderDefended = (int) $dispute->current_holder_id === $verifierId;
            $status = $holderDefended ? DisputeStatus::ResolvedDenied : DisputeStatus::AutoResolved;

            $dispute->decision_note = $holderDefended
                ? 'Holder re-verified with an in-game token; the claim is denied.'
                : 'Resolved automatically by a token verification on the tag.';
            $dispute->forceFill([
                'status' => $status->value,
                'decided_by' => $verifierId,
                'decided_at' => now(),
            ])->save();

            CocAccountClaim::query()
                ->where('tag_normalized', $normalized)
                ->where('user_id', $dispute->claimant_id)
                ->where('method', 'dispute')
                ->where('status', ClaimStatus::Pending->value)
                ->update([
                    'status' => ($dispute->claimant_id === $verifierId ? ClaimStatus::Succeeded : ClaimStatus::Rejected)->value,
                ]);

            event(new NoticeRequested($dispute->claimant_id, NoticeKind::DisputeDecided));
            if ($dispute->current_holder_id !== null && (int) $dispute->current_holder_id !== $dispute->claimant_id) {
                event(new NoticeRequested((int) $dispute->current_holder_id, NoticeKind::DisputeDecided));
            }
        }
    }

    private function fetchPlayer(PlayerTag $tag): PlayerData
    {
        try {
            return $this->players->find($tag);
        } catch (CocApiException $e) {
            throw AccountAttachException::of($e->isNotFound() ? AttachError::PlayerNotFound : AttachError::ApiUnavailable);
        }
    }

    private function verifiedHolder(string $normalized): ?CocAccount
    {
        return CocAccount::query()
            ->where('tag_normalized', $normalized)
            ->where('status', CocAccountStatus::Verified->value)
            ->first();
    }

    private function userHolds(int $userId, string $normalized): bool
    {
        return CocAccount::query()
            ->where('user_id', $userId)
            ->where('tag_normalized', $normalized)
            ->whereIn('status', [
                CocAccountStatus::Unverified->value,
                CocAccountStatus::Verified->value,
                CocAccountStatus::Disputed->value,
            ])
            ->exists();
    }

    private function claim(int $userId, string $normalized, ClaimStatus $status, ?ClaimFailureReason $reason, ?int $accountId, AuditContext $context): void
    {
        $claim = new CocAccountClaim([
            'tag_normalized' => $normalized,
            'method' => ClaimMethod::ApiToken->value,
            'failure_reason' => $reason?->value,
            'ip_hash' => $context->ipHash,
            'user_agent' => $context->userAgent,
        ]);
        $claim->status = $status;
        $claim->user_id = $userId;
        $claim->coc_account_id = $accountId;
        $claim->save();
    }

    private function tag(string $rawTag): PlayerTag
    {
        try {
            return new PlayerTag($rawTag);
        } catch (InvalidArgumentException) {
            throw AccountAttachException::of(AttachError::InvalidTag);
        }
    }

    private function normalize(PlayerTag $tag): string
    {
        return ltrim($tag->value, '#');
    }

    private function rateLimit(string $key, int $max): void
    {
        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw AccountAttachException::of(AttachError::RateLimited);
        }
        RateLimiter::hit($key, 3600);
    }

    /**
     * @return array<string, mixed>
     */
    private function syncData(PlayerData $player): array
    {
        return [
            'ign' => $player->name,
            'th_level' => max(1, $player->townHallLevel),
            'builder_hall_level' => $player->builderHallLevel,
            'builder_trophies' => $player->builderTrophies,
            'xp_level' => $player->expLevel,
            'trophies' => $player->trophies,
            'best_trophies' => $player->bestTrophies,
            'war_stars' => $player->warStars,
            'attack_wins' => $player->attackWins,
            'defense_wins' => $player->defenseWins,
            'donations' => $player->donations,
            'donations_received' => $player->donationsReceived,
            'clan_tag' => $player->clan?->tag,
            'clan_role' => $player->clan?->role,
            'league_id' => $player->league?->id,
            'league_name' => $player->league?->name,
            'league_icon_url' => $player->league?->iconUrl,
            // Store the progression lists straight from the API payload so nothing is lost in mapping —
            // label icons and achievements are preserved (specs/09 §8). The per-hero `equipment` loadout
            // is dropped: every equipment piece already lives in the hero_equipment column.
            'heroes' => array_map(
                static fn (mixed $hero): mixed => is_array($hero) ? array_diff_key($hero, ['equipment' => null]) : $hero,
                $player->raw['heroes'] ?? [],
            ),
            'troops' => $player->raw['troops'] ?? [],
            'spells' => $player->raw['spells'] ?? [],
            'hero_equipment' => $player->raw['heroEquipment'] ?? [],
            'labels' => $player->raw['labels'] ?? [],
            'achievements' => $player->raw['achievements'] ?? [],
            'raw_payload' => $player->raw,
            'api_synced_at' => now(),
        ];
    }
}
