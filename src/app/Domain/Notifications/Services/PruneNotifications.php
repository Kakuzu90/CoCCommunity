<?php

namespace App\Domain\Notifications\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class PruneNotifications
{
    public function __construct(private readonly NotificationReadModel $inbox) {}

    public function run(): int
    {
        $deleted = 0;
        DB::table('users')->select('id')->orderBy('id')->chunkById((int) config('notifications.prune_chunk'), function ($users) use (&$deleted): void {
            foreach ($users as $user) {
                $deleted += $this->forUser((int) $user->id);
            }
        });

        return $deleted;
    }

    public function forUser(int $userId): int
    {
        return DB::transaction(function () use ($userId): int {
            DB::table('users')->where('id', $userId)->lockForUpdate()->first();
            $deleted = $this->inbox->owned($userId)->where(function (Builder $query): void {
                $query->where(fn (Builder $read): Builder => $read->whereNotNull('read_at')->where('created_at', '<', now()->subDays((int) config('notifications.read_retention_days'))))
                    ->orWhere(fn (Builder $unread): Builder => $unread->whereNull('read_at')->where('created_at', '<', now()->subDays((int) config('notifications.unread_retention_days'))));
            })->delete();

            $excess = $this->inbox->owned($userId)->count() - (int) config('notifications.max_rows');
            if ($excess > 0) {
                // Read rows leave first; oldest unread rows yield only to the hard per-user cap.
                $ids = $this->inbox->owned($userId)->orderByRaw('CASE WHEN read_at IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('created_at')->orderBy('id')->limit($excess)->pluck('id');
                $deleted += $this->inbox->owned($userId)->whereIn('id', $ids)->delete();
            }

            DB::afterCommit(fn () => $this->inbox->invalidate($userId));

            return $deleted;
        });
    }
}
