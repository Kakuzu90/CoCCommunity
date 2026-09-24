<?php

namespace App\Console\Commands;

use App\Domain\Auth\Services\AccountDeletionService;
use Illuminate\Console\Command;

class AnonymizeDeletedAccounts extends Command
{
    protected $signature = 'platform:anonymize-deleted';

    protected $description = 'Anonymize accounts whose deletion window has ended';

    public function handle(AccountDeletionService $deletion): int
    {
        $count = $deletion->anonymizeDue();
        $this->info("Anonymized {$count} accounts.");

        return self::SUCCESS;
    }
}
