<?php

namespace App\Domain\Moderation\Services;

use App\Domain\Audit\Data\AuditContext;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Auth\Services\UserAccountAdmin;
use App\Domain\Moderation\Data\SanctionResult;
use App\Domain\Moderation\Enums\ModerationActionType;
use App\Domain\Moderation\Enums\ReasonCode;
use App\Domain\Moderation\Enums\SanctionType;
use App\Domain\Moderation\Models\ModerationAction;
use App\Domain\Moderation\Models\UserSanction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Applies and lifts user sanctions (specs/12 §6). One sanction writes three rows atomically, each for
 * a different audience: a moderation_actions row (moderator workflow), a user_sanctions row (the
 * queryable state status checks and the expiry job read), and an audit_logs row (compliance). The
 * account-status transition and its authorization live in the Auth module — this service orchestrates,
 * it does not touch the User model. The reason is mandatory here at the service layer, not just the
 * form (specs/04 §2). Every write is inside a transaction so a denied authorization rolls all of it
 * back.
 */
final class SanctionService
{
    public function __construct(
        private readonly UserAccountAdmin $accounts,
        private readonly AuditLogger $audit,
    ) {}

    public function apply(
        int $actorId,
        int $targetId,
        SanctionType $type,
        ReasonCode $reason,
        string $publicReason,
        ?string $internalNote,
        ?int $durationDays,
    ): SanctionResult {
        return DB::transaction(function () use ($actorId, $targetId, $type, $reason, $publicReason, $internalNote, $durationDays): SanctionResult {
            $expiresAt = $type->isTimeBoxed() && $durationDays !== null
                ? CarbonImmutable::now()->addDays($durationDays)
                : null;

            // Authorizes on the action (throws → rolls back) and moves the account status.
            $change = match ($type) {
                SanctionType::Warning => $this->accounts->warn($actorId, $targetId),
                SanctionType::Restriction => $this->accounts->restrict($actorId, $targetId, $publicReason, $expiresAt),
                SanctionType::Suspension => $this->accounts->suspend($actorId, $targetId, $publicReason, $expiresAt),
                SanctionType::Ban => $this->accounts->ban($actorId, $targetId, $publicReason),
            };

            $ipHash = AuditContext::fromRequest(request())->ipHash;

            $action = new ModerationAction;
            $action->fill([
                'actor_id' => $actorId,
                'action' => $type->moderationAction()->value,
                'target_type' => 'user',
                'target_id' => $targetId,
                'target_user_id' => $targetId,
                'reason_code' => $reason->value,
                'note' => $internalNote ?? $publicReason,
                'duration_hours' => $expiresAt === null ? null : ($durationDays * 24),
                'metadata' => ['sanction_type' => $type->value],
                'ip_hash' => $ipHash,
            ]);
            $action->save();

            $sanction = new UserSanction;
            $sanction->user_id = $targetId;
            $sanction->issued_by = $actorId;
            $sanction->fill([
                'type' => $type->value,
                'reason_code' => $reason->value,
                'public_reason' => $publicReason,
                'internal_note' => $internalNote,
                'starts_at' => CarbonImmutable::now(),
                'expires_at' => $expiresAt,
                'moderation_action_id' => $action->id,
            ]);
            $sanction->save();

            $this->audit->record(
                actorId: $actorId,
                actorRole: $change->actorRole,
                action: $this->auditAction($type),
                auditableType: 'user',
                auditableId: $targetId,
                before: ['status' => $change->before->value],
                after: ['status' => $change->after->value],
                metadata: [
                    'sanction_type' => $type->value,
                    'reason_code' => $reason->value,
                    'public_reason' => $publicReason,
                    'expires_at' => $expiresAt?->toIso8601String(),
                ],
            );

            return new SanctionResult(
                sanctionId: $sanction->id,
                targetUsername: $change->targetUsername,
                type: $type,
                beforeStatus: $change->before,
                afterStatus: $change->after,
                expiresAt: $expiresAt,
            );
        });
    }

    /**
     * Lift every active sanction on a user and restore the account to active (specs/12 §6: lifting
     * requires a reason and is itself audited). The reason is a free-text note, recorded under the
     * `other` code since it is not a taxonomy classification of an offence.
     */
    public function lift(int $actorId, int $targetId, string $reason): SanctionResult
    {
        return DB::transaction(function () use ($actorId, $targetId, $reason): SanctionResult {
            $change = $this->accounts->lift($actorId, $targetId);

            $ipHash = AuditContext::fromRequest(request())->ipHash;

            $action = new ModerationAction;
            $action->fill([
                'actor_id' => $actorId,
                'action' => ModerationActionType::Unban->value,
                'target_type' => 'user',
                'target_id' => $targetId,
                'target_user_id' => $targetId,
                'reason_code' => ReasonCode::Other->value,
                'note' => $reason,
                'ip_hash' => $ipHash,
            ]);
            $action->save();

            UserSanction::query()
                ->where('user_id', $targetId)
                ->whereNull('lifted_at')
                ->update(['lifted_by' => $actorId, 'lifted_at' => CarbonImmutable::now()]);

            $this->audit->record(
                actorId: $actorId,
                actorRole: $change->actorRole,
                action: AuditAction::SanctionLifted,
                auditableType: 'user',
                auditableId: $targetId,
                before: ['status' => $change->before->value],
                after: ['status' => $change->after->value],
                metadata: ['reason' => $reason],
            );

            return new SanctionResult(
                sanctionId: $action->id,
                targetUsername: $change->targetUsername,
                type: null,
                beforeStatus: $change->before,
                afterStatus: $change->after,
                expiresAt: null,
            );
        });
    }

    private function auditAction(SanctionType $type): AuditAction
    {
        return match ($type) {
            SanctionType::Warning => AuditAction::UserWarned,
            SanctionType::Restriction => AuditAction::UserRestricted,
            SanctionType::Suspension => AuditAction::UserSuspended,
            SanctionType::Ban => AuditAction::UserBanned,
        };
    }
}
