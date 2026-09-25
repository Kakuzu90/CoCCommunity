<?php

namespace App\Domain\PlayerAccounts\Services;

use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Jobs\SyncCocAccountJob;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Models\SyncState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

final class AccountSyncScheduler
{
    public function __construct(private readonly AccountSyncService $sync) {}

    public function dispatchDue(): int
    {
        $this->seedMissingStates();

        $perMinute = (int) config('coc.rate.global_per_minute');
        $share = (float) config('coc.rate.interactive_share');
        $remaining = max(0, (int) floor($perMinute * (1 - $share)) - RateLimiter::attempts('coc-global-m'));
        $limit = (int) min((int) config('coc.sync.batch_size'), $remaining);
        if ($limit <= 0) {
            return 0;
        }

        $ids = DB::table('sync_states')
            ->join('coc_accounts', function ($join): void {
                $join->on('sync_states.resource_id', '=', 'coc_accounts.id');
            })
            ->where('sync_states.resource_type', 'coc_account')
            ->where('coc_accounts.status', CocAccountStatus::Verified->value)
            ->whereNull('coc_accounts.deleted_at')
            ->whereNotNull('sync_states.next_due_at')
            ->where('sync_states.next_due_at', '<=', now())
            ->orderBy('sync_states.next_due_at')
            ->limit($limit)
            ->pluck('coc_accounts.id');

        foreach ($ids as $id) {
            SyncState::query()
                ->where('resource_type', 'coc_account')
                ->where('resource_id', $id)
                ->update(['next_due_at' => now()->addSeconds((int) config('coc.sync.scheduler_seconds'))]);
            SyncCocAccountJob::dispatch((int) $id);
        }

        return $ids->count();
    }

    private function seedMissingStates(): void
    {
        CocAccount::query()
            ->where('status', CocAccountStatus::Verified->value)
            ->whereNotNull('user_id')
            ->whereNotIn('id', SyncState::query()->where('resource_type', 'coc_account')->select('resource_id'))
            ->orderBy('id')
            ->chunkById((int) config('coc.sync.batch_size'), function ($accounts): void {
                foreach ($accounts as $account) {
                    $this->sync->initialize((int) $account->id);
                }
            });
    }
}
