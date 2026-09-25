<?php

namespace App\Domain\PlayerAccounts\Listeners;

use App\Domain\PlayerAccounts\Events\CocAccountVerified;
use App\Domain\PlayerAccounts\Services\AccountSyncService;

final readonly class InitializeAccountSync
{
    public function __construct(private AccountSyncService $sync) {}

    public function handle(CocAccountVerified $event): void
    {
        $this->sync->initialize($event->accountId);
    }
}
