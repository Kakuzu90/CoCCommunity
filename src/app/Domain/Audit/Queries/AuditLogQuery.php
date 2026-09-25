<?php

namespace App\Domain\Audit\Queries;

use App\Domain\Audit\Data\AuditEntry;
use App\Domain\Audit\Enums\AuditAction;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Read side of the audit log for the admin viewer (specs/12 AuditTrailList, specs/04 §2). Returns
 * DTOs, never models. Actor and (when the target is a user) auditable usernames are resolved by join
 * so Presentation stays off the User model. The `'user'` morph alias is defined in AppServiceProvider.
 */
final class AuditLogQuery
{
    /**
     * @param  array{action?: string|null, actor?: string|null}  $filters
     * @return LengthAwarePaginator<int, AuditEntry>
     */
    public function paginate(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $paginator = DB::table('audit_logs')
            ->leftJoin('users as actors', 'actors.id', '=', 'audit_logs.actor_id')
            ->leftJoin('users as targets', function ($join): void {
                $join->on('targets.id', '=', 'audit_logs.auditable_id')
                    ->where('audit_logs.auditable_type', '=', 'user');
            })
            ->when(
                isset($filters['action']) && $filters['action'] !== '',
                fn ($q) => $q->where('audit_logs.action', $filters['action'])
            )
            ->when(
                isset($filters['actor']) && $filters['actor'] !== '',
                fn ($q) => $q->where('actors.username', $filters['actor'])
            )
            ->orderByDesc('audit_logs.id')
            ->select(
                'audit_logs.*',
                'actors.username as actor_username',
                'targets.username as target_username',
            )
            ->paginate($perPage);

        $paginator->through(fn (object $row): AuditEntry => $this->toEntry($row));

        return $paginator;
    }

    /** @return list<array{value: string, label: string}> distinct actions present, for the filter bar */
    public function actionOptions(): array
    {
        return DB::table('audit_logs')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->map(fn (string $action): array => ['value' => $action, 'label' => AuditAction::labelFor($action)])
            ->all();
    }

    private function toEntry(object $row): AuditEntry
    {
        return new AuditEntry(
            id: (int) $row->id,
            action: $row->action,
            actionLabel: AuditAction::labelFor($row->action),
            actorUsername: $row->actor_username,
            actorRole: $row->actor_role,
            auditableType: $row->auditable_type,
            auditableId: $row->auditable_id === null ? null : (int) $row->auditable_id,
            auditableUsername: $row->target_username,
            before: $this->decode($row->before),
            after: $this->decode($row->after),
            context: $this->decode($row->context),
            requestId: $row->request_id,
            createdAt: CarbonImmutable::parse($row->created_at),
        );
    }

    /** @return array<string, mixed>|null */
    private function decode(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        // Postgres returns arrays already decoded through the driver; SQLite returns the JSON string.
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
