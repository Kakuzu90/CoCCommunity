<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Jobs;

use App\Modules\PlayerAccounts\Enums\AccountState;
use App\Modules\PlayerAccounts\Models\CocAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Scheduled fan-out: enqueues a sync for verified accounts whose snapshot has
 * gone stale, staggered by oldest-first and capped per pass to respect API
 * rate limits.
 */
class RefreshStaleAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $staleBefore = now()->subHours((int) config('coc.sync.stale_after_hours'));

        CocAccount::query()
            ->where('state', AccountState::Verified->value)
            ->where(function ($query) use ($staleBefore): void {
                $query->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<', $staleBefore);
            })
            ->orderByRaw('last_synced_at ASC NULLS FIRST')
            ->limit((int) config('coc.sync.batch'))
            ->get()
            ->each(fn (CocAccount $account) => SyncCocAccount::dispatch($account->id));
    }
}
