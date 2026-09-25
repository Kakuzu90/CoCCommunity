<?php

namespace App\Domain\PlayerAccounts\Services;

use App\Domain\Audit\Data\AuditContext;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Media\Contracts\MediaLibrary;
use App\Domain\Media\Enums\MediaCollection;
use App\Domain\Notifications\Enums\NoticeKind;
use App\Domain\Notifications\Events\NoticeRequested;
use App\Domain\PlayerAccounts\Data\DisputeSummary;
use App\Domain\PlayerAccounts\Enums\ClaimMethod;
use App\Domain\PlayerAccounts\Enums\ClaimStatus;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Enums\DisputeError;
use App\Domain\PlayerAccounts\Enums\DisputeStatus;
use App\Domain\PlayerAccounts\Enums\HolderResponse;
use App\Domain\PlayerAccounts\Exceptions\DisputeException;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Models\CocAccountClaim;
use App\Domain\PlayerAccounts\Models\CocAccountDispute;
use App\Support\ValueObjects\PlayerTag;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * The claimant/holder side of the dispute workflow (specs/13 §5). Opening a dispute is the manual,
 * evidence-based fallback for the honest owner who cannot produce a token: it never grants ownership
 * on its own — it moves the tag to `disputed` (the holder keeps their rights) and routes a human
 * decision. The guardrails here are the anti-griefing layer: one live dispute per tag per user, a cap
 * on concurrent disputes, and a 90-day bar after repeated denials. Admin decisions and the ownership
 * move itself live in {@see DisputeResolutionService}.
 */
final class DisputeService
{
    public function __construct(
        private readonly DisputeResolutionService $resolution,
        private readonly MediaLibrary $media,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * File a dispute against the current verified holder of a tag (specs/13 §5 step 1).
     *
     * @param  array<int, string>  $mediaUlids  up to config('coc.dispute.max_evidence') evidence images
     *
     * @throws DisputeException
     */
    public function open(Authenticatable $user, string $rawTag, string $reason, string $notes = '', array $mediaUlids = [], ?AuditContext $context = null): DisputeSummary
    {
        $context ??= AuditContext::fromRequest(request());
        $userId = (int) $user->getAuthIdentifier();
        $normalized = $this->normalize($rawTag);

        return DB::transaction(function () use ($user, $userId, $normalized, $reason, $notes, $mediaUlids, $context): DisputeSummary {
            $held = CocAccount::query()
                ->where('tag_normalized', $normalized)
                ->whereIn('status', [CocAccountStatus::Verified->value, CocAccountStatus::Disputed->value])
                ->lockForUpdate()
                ->first();

            if ($held === null) {
                throw DisputeException::of(DisputeError::NoConflict);
            }
            if ((int) $held->user_id === $userId) {
                throw DisputeException::of(DisputeError::SelfDispute);
            }

            $live = CocAccountDispute::query()
                ->where('tag_normalized', $normalized)
                ->whereIn('status', DisputeStatus::liveValues())
                ->get();
            if ($live->contains(fn (CocAccountDispute $d): bool => $d->claimant_id === $userId)) {
                throw DisputeException::of(DisputeError::AlreadyDisputing);
            }
            if ($live->isNotEmpty()) {
                throw DisputeException::of(DisputeError::UnderReview);
            }

            $openCount = CocAccountDispute::query()
                ->where('claimant_id', $userId)
                ->whereIn('status', DisputeStatus::liveValues())
                ->count();
            if ($openCount >= (int) config('coc.dispute.max_open_per_user')) {
                throw DisputeException::of(DisputeError::TooManyOpen);
            }

            $recentDenied = CocAccountDispute::query()
                ->where('claimant_id', $userId)
                ->where('status', DisputeStatus::ResolvedDenied->value)
                ->where('decided_at', '>=', Carbon::now()->subDays((int) config('coc.dispute.denied_bar_days')))
                ->count();
            if ($recentDenied >= (int) config('coc.dispute.denied_bar_count')) {
                throw DisputeException::of(DisputeError::Barred);
            }

            $dispute = new CocAccountDispute([
                'ulid' => (string) Str::ulid(),
                'tag_normalized' => $normalized,
                'reason' => $reason,
                'evidence' => ['notes' => $notes, 'media' => []],
            ]);
            $dispute->forceFill([
                'coc_account_id' => $held->id,
                'claimant_id' => $userId,
                'current_holder_id' => $held->user_id,
                'status' => DisputeStatus::AwaitingHolder->value,
                'holder_responds_by' => Carbon::now()->addDays((int) config('coc.dispute.holder_response_days')),
                'last_claimant_activity_at' => Carbon::now(),
            ]);
            $dispute->save();

            $mediaIds = $this->attachEvidence($user, $dispute, $mediaUlids);
            if ($mediaIds !== []) {
                $dispute->evidence = ['notes' => $notes, 'media' => $mediaIds];
                $dispute->save();
            }

            $held->forceFill(['status' => CocAccountStatus::Disputed->value])->save();

            $claim = new CocAccountClaim([
                'tag_normalized' => $normalized,
                'method' => ClaimMethod::Dispute->value,
                'ip_hash' => $context->ipHash,
                'user_agent' => $context->userAgent,
            ]);
            $claim->status = ClaimStatus::Pending;
            $claim->user_id = $userId;
            $claim->coc_account_id = (int) $held->id;
            $claim->save();

            $this->audit->record(
                actorId: $userId,
                actorRole: null,
                action: AuditAction::CocDisputeOpened,
                auditableType: (new CocAccount)->getMorphClass(),
                auditableId: (int) $held->id,
                before: ['status' => CocAccountStatus::Verified->value],
                after: ['status' => CocAccountStatus::Disputed->value],
                metadata: ['dispute_id' => $dispute->id],
                context: $context,
            );

            if ($held->user_id !== null) {
                event(new NoticeRequested((int) $held->user_id, NoticeKind::DisputeOpened));
            }

            return DisputeSummary::fromModel($dispute);
        });
    }

    /**
     * The current holder answers a dispute (specs/13 §5 step 3). A counter-statement routes it to admin
     * review; a voluntary release transfers the tag to the claimant at once. (Re-verifying with a token
     * is the fourth option and runs through the ordinary verification path, not here.)
     *
     * @param  array<int, string>  $mediaUlids
     *
     * @throws DisputeException
     */
    public function respondByHolder(int $holderId, int $disputeId, HolderResponse $response, string $notes = '', array $mediaUlids = [], ?Authenticatable $user = null, ?AuditContext $context = null): void
    {
        $context ??= AuditContext::fromRequest(request());

        DB::transaction(function () use ($holderId, $disputeId, $response, $notes, $mediaUlids, $user, $context): void {
            $dispute = CocAccountDispute::query()->whereKey($disputeId)->lockForUpdate()->firstOrFail();

            if ((int) $dispute->current_holder_id !== $holderId) {
                throw DisputeException::of(DisputeError::NotYours);
            }
            if (! $dispute->status->isLive()) {
                throw DisputeException::of(DisputeError::NotResolvable);
            }

            if ($response === HolderResponse::Release) {
                $this->resolution->voluntaryRelease($holderId, $dispute, $context);

                return;
            }

            $mediaIds = $user !== null ? $this->attachEvidence($user, $dispute, $mediaUlids) : [];
            $evidence = $dispute->evidence;
            $evidence['holder_response'] = ['notes' => $notes, 'media' => $mediaIds];
            $dispute->evidence = $evidence;
            $dispute->forceFill(['status' => DisputeStatus::Open->value])->save();
        });
    }

    /**
     * The claimant supplies more information after an admin request (specs/13 §5 step 4). Returns the
     * dispute to the admin queue.
     *
     * @param  array<int, string>  $mediaUlids
     *
     * @throws DisputeException
     */
    public function respondByClaimant(int $claimantId, int $disputeId, string $notes, array $mediaUlids = [], ?Authenticatable $user = null): void
    {
        DB::transaction(function () use ($claimantId, $disputeId, $notes, $mediaUlids, $user): void {
            $dispute = CocAccountDispute::query()->whereKey($disputeId)->lockForUpdate()->firstOrFail();

            if ($dispute->claimant_id !== $claimantId) {
                throw DisputeException::of(DisputeError::NotYours);
            }
            if ($dispute->status !== DisputeStatus::AwaitingClaimant) {
                throw DisputeException::of(DisputeError::NotResolvable);
            }

            $mediaIds = $user !== null ? $this->attachEvidence($user, $dispute, $mediaUlids) : [];
            $evidence = $dispute->evidence;
            $evidence['notes'] = trim(($evidence['notes'] ?? '')."\n\n".$notes);
            $evidence['media'] = array_values(array_unique(array_merge((array) ($evidence['media'] ?? []), $mediaIds)));
            $dispute->evidence = $evidence;
            $dispute->forceFill([
                'status' => DisputeStatus::Open->value,
                'last_claimant_activity_at' => Carbon::now(),
            ])->save();
        });
    }

    /**
     * The claimant abandons a dispute (specs/13 §5 guardrails: auto-close after inactivity is the same
     * transition). The tag returns to its holder unchanged.
     *
     * @throws DisputeException
     */
    public function withdraw(int $claimantId, int $disputeId, ?AuditContext $context = null): void
    {
        $context ??= AuditContext::fromRequest(request());

        DB::transaction(function () use ($claimantId, $disputeId, $context): void {
            $dispute = CocAccountDispute::query()->whereKey($disputeId)->lockForUpdate()->firstOrFail();

            if ($dispute->claimant_id !== $claimantId) {
                throw DisputeException::of(DisputeError::NotYours);
            }
            if (! $dispute->status->isLive()) {
                throw DisputeException::of(DisputeError::NotResolvable);
            }

            $held = CocAccount::query()->whereKey($dispute->coc_account_id)->lockForUpdate()->first();
            if ($held !== null && $held->status === CocAccountStatus::Disputed) {
                $held->forceFill(['status' => CocAccountStatus::Verified->value])->save();
            }

            $dispute->forceFill([
                'status' => DisputeStatus::Withdrawn->value,
                'decided_by' => $claimantId,
                'decided_at' => Carbon::now(),
            ])->save();

            CocAccountClaim::query()
                ->where('tag_normalized', $dispute->tag_normalized)
                ->where('user_id', $claimantId)
                ->where('method', ClaimMethod::Dispute->value)
                ->where('status', ClaimStatus::Pending->value)
                ->update(['status' => ClaimStatus::Rejected->value]);

            $this->audit->record(
                actorId: $claimantId,
                actorRole: null,
                action: 'coc_dispute.withdrawn',
                auditableType: (new CocAccount)->getMorphClass(),
                auditableId: (int) $dispute->coc_account_id,
                metadata: ['dispute_id' => $dispute->id],
                context: $context,
            );
        });
    }

    /**
     * @param  array<int, string>  $mediaUlids
     * @return array<int, int>
     */
    private function attachEvidence(Authenticatable $user, CocAccountDispute $dispute, array $mediaUlids): array
    {
        $max = (int) config('coc.dispute.max_evidence');
        $ids = [];
        foreach (array_slice(array_values($mediaUlids), 0, $max) as $ulid) {
            $ids[] = $this->media->attach($user, $ulid, MediaCollection::Evidence, $dispute);
        }

        return $ids;
    }

    private function normalize(string $rawTag): string
    {
        try {
            return ltrim((new PlayerTag($rawTag))->value, '#');
        } catch (InvalidArgumentException) {
            throw DisputeException::of(DisputeError::NoConflict);
        }
    }
}
