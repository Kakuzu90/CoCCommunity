<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Services;

use App\Modules\Moderation\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Public entry point other modules use to append to the audit trail. Keeps
 * the audit_logs table owned by Moderation while callers stay decoupled.
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function log(
        ?int $actorId,
        string $action,
        Model $subject,
        ?array $before = null,
        ?array $after = null,
    ): void {
        AuditLog::create([
            'actor_id' => $actorId,
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'before' => $before,
            'after' => $after,
            'ip' => optional(request())->ip(),
        ]);
    }
}
