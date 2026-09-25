<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Data\AuditContext;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Models\AuditLog;

/**
 * The Audit module's only write entry point (specs/07 `audit_logs`, specs/04 §2). Every privileged or
 * ownership-changing action calls this; it is deliberately a leaf that depends on nothing but its own
 * model, so any module can record to it without creating a dependency cycle. Append-only: this writes,
 * it never reads back or mutates.
 */
final class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>|null  $metadata  extra context merged alongside the request stamp
     */
    public function record(
        ?int $actorId,
        ?string $actorRole,
        AuditAction|string $action,
        ?string $auditableType = null,
        ?int $auditableId = null,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null,
        ?AuditContext $context = null,
    ): void {
        $context ??= AuditContext::fromRequest(request());

        AuditLog::create([
            'actor_id' => $actorId,
            'actor_role' => $actorRole,
            'action' => $action instanceof AuditAction ? $action->value : $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'before' => $before,
            'after' => $after,
            'context' => $metadata,
            'ip_hash' => $context->ipHash,
            'user_agent' => $context->userAgent,
            'request_id' => $context->requestId,
        ]);
    }
}
