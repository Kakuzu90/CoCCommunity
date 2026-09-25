<?php

namespace App\Domain\PlayerAccounts\Services;

use App\Domain\Audit\Data\AuditContext;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Auth\Services\VerifiedAccountCounter;
use App\Domain\Moderation\Enums\ModerationActionType;
use App\Domain\Moderation\Enums\ReasonCode;
use App\Domain\Moderation\Services\ModerationRecorder;
use App\Domain\Notifications\Enums\NoticeKind;
use App\Domain\Notifications\Events\NoticeRequested;
use App\Domain\PlayerAccounts\Enums\ClaimStatus;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Enums\DisputeError;
use App\Domain\PlayerAccounts\Enums\DisputeStatus;
use App\Domain\PlayerAccounts\Enums\VerificationMethod;
use App\Domain\PlayerAccounts\Events\CocAccountVerified;
use App\Domain\PlayerAccounts\Exceptions\DisputeException;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Models\CocAccountClaim;
use App\Domain\PlayerAccounts\Models\CocAccountDispute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Executes the terminal decisions on a dispute (specs/13 §5 steps 4–6): an admin transfers, denies or
 * suspends, and a holder may voluntarily release. Every decision is one transaction with a row-level
 * lock on the tag, so it serialises against a concurrent token verification. Ownership always keeps the
 * same in-game player's synced data by cloning the held row, so snapshots and stats survive the move.
 * The bias is fixed by the spec: absent decisive evidence the current holder keeps the tag, so "deny"
 * is the safe default and "transfer" is the deliberate act.
 */
final class DisputeResolutionService
{
    public function __construct(
        private readonly VerifiedAccountCounter $counter,
        private readonly ModerationRecorder $moderation,
        private readonly AuditLogger $audit,
    ) {}

    /** Admin transfers the tag to the claimant (specs/13 §5 step 5 transfer). */
    public function transfer(int $adminId, int $disputeId, string $note, ?AuditContext $context = null): void
    {
        $context ??= AuditContext::fromRequest(request());

        DB::transaction(function () use ($adminId, $disputeId, $note, $context): void {
            $dispute = $this->lockDispute($disputeId);
            $this->assertAdminCanDecide($adminId, $dispute);

            $this->moveOwnership($dispute, $dispute->claimant_id, VerificationMethod::Admin, $context);

            $moderationId = $this->moderation->record(
                actorId: $adminId,
                action: ModerationActionType::TransferOwnership,
                targetType: (new CocAccount)->getMorphClass(),
                targetId: $dispute->coc_account_id,
                reason: ReasonCode::FalseOwnership,
                note: $note,
                targetUserId: $dispute->claimant_id,
                metadata: ['dispute_id' => $dispute->id],
            );

            $this->closeDispute($dispute, DisputeStatus::ResolvedTransfer, $adminId, $note);
            $this->updateDisputeClaims($dispute, ClaimStatus::Succeeded);

            $this->audit->record(
                actorId: $adminId,
                actorRole: 'admin',
                action: AuditAction::CocDisputeTransferred,
                auditableType: (new CocAccount)->getMorphClass(),
                auditableId: $dispute->coc_account_id,
                before: ['user_id' => $dispute->current_holder_id],
                after: ['user_id' => $dispute->claimant_id],
                metadata: ['dispute_id' => $dispute->id, 'moderation_action_id' => $moderationId, 'note' => $note],
                context: $context,
            );

            $this->notifyBoth($dispute);
        });
    }

    /** Admin denies the claim; the holder keeps the tag (specs/13 §5 step 5 deny; decision bias). */
    public function deny(int $adminId, int $disputeId, string $note, ?AuditContext $context = null): void
    {
        $context ??= AuditContext::fromRequest(request());

        DB::transaction(function () use ($adminId, $disputeId, $note, $context): void {
            $dispute = $this->lockDispute($disputeId);
            $this->assertAdminCanDecide($adminId, $dispute);

            $this->restoreHolder($dispute);

            $moderationId = $this->moderation->record(
                actorId: $adminId,
                action: ModerationActionType::Dismiss,
                targetType: (new CocAccount)->getMorphClass(),
                targetId: $dispute->coc_account_id,
                reason: ReasonCode::FalseOwnership,
                note: $note,
                targetUserId: $dispute->claimant_id,
                metadata: ['dispute_id' => $dispute->id],
            );

            $this->closeDispute($dispute, DisputeStatus::ResolvedDenied, $adminId, $note);
            $this->updateDisputeClaims($dispute, ClaimStatus::Rejected);

            $this->audit->record(
                actorId: $adminId,
                actorRole: 'admin',
                action: AuditAction::CocDisputeDenied,
                auditableType: (new CocAccount)->getMorphClass(),
                auditableId: $dispute->coc_account_id,
                before: ['status' => CocAccountStatus::Disputed->value],
                after: ['status' => CocAccountStatus::Verified->value],
                metadata: ['dispute_id' => $dispute->id, 'moderation_action_id' => $moderationId, 'note' => $note],
                context: $context,
            );

            $this->notifyBoth($dispute);
        });
    }

    /** Admin suspends the tag: both parties look fraudulent, neither gets it (specs/13 §5 step 5 suspend). */
    public function suspend(int $adminId, int $disputeId, string $note, ?AuditContext $context = null): void
    {
        $context ??= AuditContext::fromRequest(request());

        DB::transaction(function () use ($adminId, $disputeId, $note, $context): void {
            $dispute = $this->lockDispute($disputeId);
            $this->assertAdminCanDecide($adminId, $dispute);

            $held = CocAccount::query()->whereKey($dispute->coc_account_id)->lockForUpdate()->first();
            if ($held !== null) {
                if ($held->status === CocAccountStatus::Verified && $held->user_id !== null) {
                    $this->counter->decrement((int) $held->user_id);
                }
                $held->forceFill(['status' => CocAccountStatus::Suspended->value])->save();
            }

            $moderationId = $this->moderation->record(
                actorId: $adminId,
                action: ModerationActionType::Suspend,
                targetType: (new CocAccount)->getMorphClass(),
                targetId: $dispute->coc_account_id,
                reason: ReasonCode::FalseOwnership,
                note: $note,
                targetUserId: $dispute->current_holder_id,
                metadata: ['dispute_id' => $dispute->id],
            );

            $this->closeDispute($dispute, DisputeStatus::ResolvedDenied, $adminId, $note);
            $this->updateDisputeClaims($dispute, ClaimStatus::Rejected);

            $this->audit->record(
                actorId: $adminId,
                actorRole: 'admin',
                action: AuditAction::CocDisputeTagSuspended,
                auditableType: (new CocAccount)->getMorphClass(),
                auditableId: $dispute->coc_account_id,
                before: ['status' => CocAccountStatus::Disputed->value],
                after: ['status' => CocAccountStatus::Suspended->value],
                metadata: ['dispute_id' => $dispute->id, 'moderation_action_id' => $moderationId, 'note' => $note],
                context: $context,
            );

            $this->notifyBoth($dispute);
        });
    }

    /** Admin asks the claimant for more information (specs/13 §5 step 4 "request more information"). */
    public function requestMoreInfo(int $adminId, int $disputeId, string $note, ?AuditContext $context = null): void
    {
        DB::transaction(function () use ($adminId, $disputeId, $note): void {
            $dispute = $this->lockDispute($disputeId);
            $this->assertAdminCanDecide($adminId, $dispute);

            $dispute->decision_note = $note;
            $dispute->forceFill([
                'status' => DisputeStatus::AwaitingClaimant->value,
                'assigned_admin_id' => $adminId,
            ])->save();

            event(new NoticeRequested($dispute->claimant_id, NoticeKind::DisputeDecided));
        });
    }

    /** Holder voluntarily hands the tag to the claimant (specs/13 §5 step 3c). */
    public function voluntaryRelease(int $holderId, CocAccountDispute $dispute, AuditContext $context): void
    {
        $this->moveOwnership($dispute, $dispute->claimant_id, VerificationMethod::Admin, $context);
        $this->closeDispute($dispute, DisputeStatus::ResolvedTransfer, $holderId, 'Holder voluntarily released the tag.');
        $this->updateDisputeClaims($dispute, ClaimStatus::Succeeded);

        $this->audit->record(
            actorId: $holderId,
            actorRole: null,
            action: AuditAction::CocDisputeTransferred,
            auditableType: (new CocAccount)->getMorphClass(),
            auditableId: $dispute->coc_account_id,
            before: ['user_id' => $dispute->current_holder_id],
            after: ['user_id' => $dispute->claimant_id],
            metadata: ['dispute_id' => $dispute->id, 'voluntary' => true],
            context: $context,
        );

        $this->notifyBoth($dispute);
    }

    /**
     * The single ownership-move transaction body (specs/13 §3.1 applied to an admin decision). Assumes
     * the dispute row is already locked. Demotes the current holder, clones the in-game player's synced
     * data into the new owner's row (reusing a released row when one exists) and promotes it to verified.
     */
    private function moveOwnership(CocAccountDispute $dispute, int $newOwnerId, VerificationMethod $method, AuditContext $context): void
    {
        $rows = CocAccount::query()
            ->where('tag_normalized', $dispute->tag_normalized)
            ->lockForUpdate()
            ->get();

        $held = $rows->firstWhere('id', $dispute->coc_account_id) ?? $rows->first();

        $previousHolderId = null;
        foreach ($rows as $row) {
            if ($row->user_id !== null && (int) $row->user_id !== $newOwnerId
                && in_array($row->status, [CocAccountStatus::Verified, CocAccountStatus::Disputed], true)) {
                $previousHolderId = (int) $row->user_id;
                $row->forceFill([
                    'status' => CocAccountStatus::Unverified->value,
                    'previous_user_id' => $previousHolderId,
                ])->save();
                CocAccountClaim::query()
                    ->where('coc_account_id', $row->id)
                    ->whereIn('status', [ClaimStatus::Pending->value, ClaimStatus::Succeeded->value])
                    ->update(['status' => ClaimStatus::Superseded->value]);
                // Both verified and disputed holders carried a verified count, so they lose the badge.
                $this->counter->decrement($previousHolderId);
            }
        }

        $account = $rows->first(fn (CocAccount $r): bool => (int) $r->user_id === $newOwnerId)
            ?? $rows->first(fn (CocAccount $r): bool => $r->status === CocAccountStatus::Released)
            ?? new CocAccount;

        if (! $account->exists) {
            $account->fill($this->cloneSyncData($held));
            $account->ulid = (string) Str::ulid();
        }

        $account->forceFill([
            'user_id' => $newOwnerId,
            'tag' => $held->tag,
            'tag_normalized' => $dispute->tag_normalized,
            'status' => CocAccountStatus::Verified->value,
            'verified_at' => now(),
            'verification_method' => $method->value,
            'api_sync_failures' => 0,
        ]);

        $isFirst = CocAccount::query()
            ->where('user_id', $newOwnerId)
            ->where('status', CocAccountStatus::Verified->value)
            ->when($account->exists, fn ($q) => $q->where('id', '!=', $account->id))
            ->doesntExist();
        if ($isFirst) {
            $account->forceFill(['is_featured' => true]);
        }

        $account->save();

        $this->counter->increment($newOwnerId);
        event(new CocAccountVerified((int) $account->id, $newOwnerId, $previousHolderId));
    }

    /** Return a still-disputed account to its holder unchanged (deny / withdraw). */
    private function restoreHolder(CocAccountDispute $dispute): void
    {
        $held = CocAccount::query()->whereKey($dispute->coc_account_id)->lockForUpdate()->first();
        // Only restore if a concurrent token verification has not already moved the tag.
        if ($held !== null && $held->status === CocAccountStatus::Disputed) {
            $held->forceFill(['status' => CocAccountStatus::Verified->value])->save();
        }
    }

    private function closeDispute(CocAccountDispute $dispute, DisputeStatus $status, int $decidedBy, string $note): void
    {
        $dispute->decision_note = $note;
        $dispute->forceFill([
            'status' => $status->value,
            'decided_by' => $decidedBy,
            'decided_at' => Carbon::now(),
        ])->save();
    }

    private function updateDisputeClaims(CocAccountDispute $dispute, ClaimStatus $status): void
    {
        CocAccountClaim::query()
            ->where('tag_normalized', $dispute->tag_normalized)
            ->where('user_id', $dispute->claimant_id)
            ->where('method', 'dispute')
            ->where('status', ClaimStatus::Pending->value)
            ->update(['status' => $status->value]);
    }

    private function notifyBoth(CocAccountDispute $dispute): void
    {
        event(new NoticeRequested($dispute->claimant_id, NoticeKind::DisputeDecided));
        if ($dispute->current_holder_id !== null) {
            event(new NoticeRequested((int) $dispute->current_holder_id, NoticeKind::DisputeDecided));
        }
    }

    private function assertAdminCanDecide(int $adminId, CocAccountDispute $dispute): void
    {
        if ($adminId === $dispute->claimant_id || $adminId === (int) $dispute->current_holder_id) {
            throw DisputeException::of(DisputeError::AdminIsParty);
        }
        if (! $dispute->status->isLive()) {
            throw DisputeException::of(DisputeError::NotResolvable);
        }
    }

    private function lockDispute(int $disputeId): CocAccountDispute
    {
        return CocAccountDispute::query()->whereKey($disputeId)->lockForUpdate()->firstOrFail();
    }

    /**
     * Copy the in-game player's synced columns from the held row so the new owner's row carries the same
     * stats immediately; a later sync refreshes them. Progression blobs come along untouched.
     *
     * @return array<string, mixed>
     */
    private function cloneSyncData(?CocAccount $held): array
    {
        if ($held === null) {
            return ['ign' => 'Unknown', 'th_level' => 1];
        }

        return [
            'ign' => $held->ign,
            'th_level' => $held->th_level,
            'builder_hall_level' => $held->builder_hall_level,
            'xp_level' => $held->xp_level,
            'trophies' => $held->trophies,
            'best_trophies' => $held->best_trophies,
            'builder_trophies' => $held->builder_trophies,
            'war_stars' => $held->war_stars,
            'attack_wins' => $held->attack_wins,
            'defense_wins' => $held->defense_wins,
            'donations' => $held->donations,
            'donations_received' => $held->donations_received,
            'clan_tag' => $held->clan_tag,
            'clan_role' => $held->clan_role,
            'league_id' => $held->league_id,
            'league_name' => $held->league_name,
            'league_icon_url' => $held->league_icon_url,
            'troops' => $held->troops,
            'heroes' => $held->heroes,
            'spells' => $held->spells,
            'hero_equipment' => $held->hero_equipment,
            'labels' => $held->labels,
            'achievements' => $held->achievements,
            'raw_payload' => $held->raw_payload,
            'api_synced_at' => $held->api_synced_at,
        ];
    }
}
