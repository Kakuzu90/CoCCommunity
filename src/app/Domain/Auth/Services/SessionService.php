<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\SessionData;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class SessionService
{
    /** @return list<SessionData> */
    public function list(Authenticatable $user, string $currentId): array
    {
        return array_values(DB::table('sessions')->where('user_id', $user->getAuthIdentifier())
            ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime'))->timestamp)
            ->where('created_at', '>=', now()->subMinutes((int) config('session.absolute_minutes')))
            ->orderByDesc('last_activity')->get()
            ->map(fn (object $row): SessionData => new SessionData(
                $row->id,
                $row->device_label ?: 'Unknown device',
                $row->ip_address,
                CarbonImmutable::createFromTimestamp($row->last_activity),
                $row->id === $currentId,
            ))->all());
    }

    /** An owned, remote session only. A foreign or current id is indistinguishable from missing. */
    public function revoke(Authenticatable $user, string $sessionId, string $currentId): bool
    {
        return DB::table('sessions')->where('user_id', $user->getAuthIdentifier())
            ->where('id', $sessionId)->where('id', '!=', $currentId)->delete() === 1;
    }

    public function revokeOthers(Authenticatable $user, string $currentId): void
    {
        DB::table('sessions')->where('user_id', $user->getAuthIdentifier())
            ->where('id', '!=', $currentId)->delete();
    }

    public function revokeAll(Authenticatable $user): void
    {
        DB::table('sessions')->where('user_id', $user->getAuthIdentifier())->delete();
    }
}
