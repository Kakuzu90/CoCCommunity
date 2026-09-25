<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\AdminUserDetail;
use App\Domain\Auth\Data\AdminUserFilters;
use App\Domain\Auth\Data\AdminUserSummary;
use App\Domain\Auth\Enums\UserRole;
use App\Domain\Auth\Enums\UserStatus;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Read side of the admin user directory (specs/12 §4, specs/04 §2 "View user list"). Returns DTOs, so
 * Presentation never holds a User model. Search is a case-insensitive prefix/contains on username and
 * email; both are indexed identity columns. Soft-deleted (anonymised) accounts are excluded.
 */
final class UserDirectory
{
    /** The handle for a user id, or null if unknown — used to name a tag's current holder (specs/13 §4). */
    public function usernameById(int $id): ?string
    {
        $username = DB::table('users')->where('id', $id)->value('username');

        return is_string($username) ? $username : null;
    }

    /**
     * Handles for a set of user ids, keyed by id — used to name the parties on a list of disputes
     * without an N+1 (specs/13 §5). Missing ids are simply absent from the map.
     *
     * @param  array<int, int>  $ids
     * @return array<int, string>
     */
    public function usernamesByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return [];
        }

        /** @var array<int, string> $map */
        $map = DB::table('users')->whereIn('id', $ids)->pluck('username', 'id')->all();

        return $map;
    }

    /**
     * @param  int|null  $excludeId  the viewing admin's own id, kept out of the list (they manage
     *                               their own account through Settings, not the moderation tools)
     * @return LengthAwarePaginator<int, AdminUserSummary>
     */
    public function paginate(AdminUserFilters $filters, int $perPage = 25, ?int $excludeId = null): LengthAwarePaginator
    {
        $paginator = DB::table('users')
            ->whereNull('deleted_at')
            ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
            ->when($filters->search !== null, function ($q) use ($filters): void {
                $term = '%'.$this->escapeLike($filters->search ?? '').'%';
                $q->where(function ($inner) use ($term): void {
                    $inner->where('username', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($filters->role !== null && UserRole::tryFrom($filters->role) !== null,
                fn ($q) => $q->where('role', $filters->role))
            ->when($filters->status !== null && UserStatus::tryFrom($filters->status) !== null,
                fn ($q) => $q->where('status', $filters->status))
            ->orderBy($filters->sort, $filters->direction)
            ->orderBy('id', 'desc')
            ->select('id', 'username', 'email', 'role', 'status', 'verified_accounts_count', 'created_at', 'last_login_at')
            ->paginate($perPage);

        $paginator->through(fn (\stdClass $row): AdminUserSummary => new AdminUserSummary(
            id: (int) $row->id,
            username: $row->username,
            email: $row->email,
            role: UserRole::from($row->role),
            status: UserStatus::from($row->status),
            verifiedAccounts: (int) $row->verified_accounts_count,
            createdAt: CarbonImmutable::parse($row->created_at),
            lastLoginAt: $row->last_login_at === null ? null : CarbonImmutable::parse($row->last_login_at),
        ));

        return $paginator;
    }

    public function find(string $username): ?AdminUserDetail
    {
        $row = DB::table('users')
            ->leftJoin('user_stats', 'user_stats.user_id', '=', 'users.id')
            ->where('users.username', $username)
            ->whereNull('users.deleted_at')
            ->select(
                'users.id', 'users.username', 'users.email', 'users.role', 'users.status',
                'users.status_reason', 'users.status_expires_at', 'users.email_verified_at',
                'users.verified_accounts_count', 'users.created_at', 'users.last_login_at',
                'users.deletion_requested_at', 'user_stats.bases_published',
            )
            ->first();

        if ($row === null) {
            return null;
        }

        return new AdminUserDetail(
            id: (int) $row->id,
            username: $row->username,
            email: $row->email,
            role: UserRole::from($row->role),
            status: UserStatus::from($row->status),
            statusReason: $row->status_reason,
            statusExpiresAt: $row->status_expires_at === null ? null : CarbonImmutable::parse($row->status_expires_at),
            emailVerified: $row->email_verified_at !== null,
            verifiedAccounts: (int) $row->verified_accounts_count,
            basesPublished: (int) ($row->bases_published ?? 0),
            createdAt: CarbonImmutable::parse($row->created_at),
            lastLoginAt: $row->last_login_at === null ? null : CarbonImmutable::parse($row->last_login_at),
            deletionRequestedAt: $row->deletion_requested_at === null ? null : CarbonImmutable::parse($row->deletion_requested_at),
        );
    }

    /** Resolve the internal id for a username (users are addressed by handle, never by id: specs/04 §3). */
    public function resolveId(string $username): ?int
    {
        $id = DB::table('users')
            ->where('username', $username)
            ->whereNull('deleted_at')
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    /** @return array{total: int, staff: int, sanctioned: int} headline counts for the dashboard */
    public function counts(): array
    {
        return [
            'total' => (int) DB::table('users')->whereNull('deleted_at')->count(),
            'staff' => (int) DB::table('users')->whereNull('deleted_at')->where('role', '!=', UserRole::User->value)->count(),
            'sanctioned' => (int) DB::table('users')->whereNull('deleted_at')
                ->whereIn('status', [UserStatus::Restricted->value, UserStatus::Suspended->value, UserStatus::Banned->value])
                ->count(),
        ];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
