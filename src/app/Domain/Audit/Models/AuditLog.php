<?php

namespace App\Domain\Audit\Models;

use App\Domain\Audit\Queries\AuditLogQuery;
use App\Domain\Audit\Services\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only compliance/forensics record (specs/07 `audit_logs`). No updates, no deletes, no
 * `updated_at`. Written only through {@see AuditLogger}; read for the admin
 * viewer through {@see AuditLogQuery}. Never surfaced to Presentation as a
 * model — the query returns DTOs.
 *
 * @property int $id
 * @property int|null $actor_id
 * @property string|null $actor_role
 * @property string $action
 * @property array<string, mixed>|null $before
 * @property array<string, mixed>|null $after
 * @property array<string, mixed>|null $context
 * @property CarbonInterface $created_at
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id', 'actor_role', 'action', 'auditable_type', 'auditable_id',
        'before', 'after', 'context', 'ip_hash', 'user_agent', 'request_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
