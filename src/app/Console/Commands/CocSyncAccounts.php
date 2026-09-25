<?php

namespace App\Console\Commands;

use App\Domain\PlayerAccounts\Services\AccountSyncScheduler;
use Illuminate\Console\Command;

final class CocSyncAccounts extends Command
{
    protected $signature = 'coc:sync-accounts';

    protected $description = 'Dispatch due Clash of Clans account sync jobs';

    public function handle(AccountSyncScheduler $scheduler): int
    {
        $count = $scheduler->dispatchDue();
        $this->info("Dispatched {$count} account sync jobs.");

        return self::SUCCESS;
    }
}
