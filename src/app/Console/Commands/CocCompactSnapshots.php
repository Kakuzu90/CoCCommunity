<?php

namespace App\Console\Commands;

use App\Domain\PlayerAccounts\Services\SnapshotCompactor;
use Illuminate\Console\Command;

final class CocCompactSnapshots extends Command
{
    protected $signature = 'coc:compact-snapshots {--dry-run}';

    protected $description = 'Compact old Clash of Clans progression snapshots';

    public function handle(SnapshotCompactor $compactor): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $removed = $compactor->compact($dryRun);
        $this->info(($dryRun ? 'Would remove ' : 'Removed ').$removed.' redundant snapshots.');

        return self::SUCCESS;
    }
}
