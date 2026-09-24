<?php

namespace App\Console\Commands;

use App\Domain\Media\Services\OrphanMediaSweeper;
use Illuminate\Console\Command;

class MediaSweepOrphans extends Command
{
    protected $signature = 'media:sweep-orphans {--dry-run : Report what would be deleted without deleting}';

    protected $description = 'Delete unattached media past its expiry and the objects behind it';

    public function handle(OrphanMediaSweeper $sweeper): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $count = $sweeper->sweep($dryRun);

        $verb = $dryRun ? 'would delete' : 'deleted';
        $this->info("media:sweep-orphans {$verb} {$count} orphaned media");

        return self::SUCCESS;
    }
}
