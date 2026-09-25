<?php

namespace App\Domain\Moderation\Services;

use App\Domain\Audit\Data\AuditContext;
use App\Domain\Moderation\Enums\ModerationActionType;
use App\Domain\Moderation\Enums\ReasonCode;
use App\Domain\Moderation\Models\ModerationAction;

/**
 * The seam other modules use to record a moderator action against their own target without reaching
 * into the {@see ModerationAction} model (specs/07, specs/19 §2). The disputes workflow in
 * PlayerAccounts records an admin's transfer/deny/suspend decision through here so the immutable
 * "what a moderator did" record has a single writer. Returns the new action id for cross-referencing.
 */
final class ModerationRecorder
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        int $actorId,
        ModerationActionType $action,
        string $targetType,
        int $targetId,
        ReasonCode $reason,
        string $note,
        ?int $targetUserId = null,
        array $metadata = [],
    ): int {
        $row = new ModerationAction;
        $row->fill([
            'actor_id' => $actorId,
            'action' => $action->value,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_user_id' => $targetUserId,
            'reason_code' => $reason->value,
            'note' => $note,
            'metadata' => $metadata === [] ? null : $metadata,
            'ip_hash' => AuditContext::fromRequest(request())->ipHash,
        ]);
        $row->save();

        return (int) $row->id;
    }
}
