<?php

namespace App\Console\Commands;

use App\Domain\Media\Services\StorageReconciler;
use Illuminate\Console\Command;

/**
 * Weekly bucket↔database reconcile (specs/10 §9). Reports orphaned objects and missing files under
 * the allowlisted prefixes only — the `game/` prefix is excluded by StorageReconciler::PREFIXES.
 */
class MediaReconcileStorage extends Command
{
    protected $signature = 'media:reconcile-storage';

    protected $description = 'Diff the media bucket against the database under the allowlisted prefixes (never game/) and report drift';

    public function handle(StorageReconciler $reconciler): int
    {
        $report = $reconciler->reconcile();

        $this->info('Scanned prefixes: '.implode(', ', $report->scannedPrefixes));
        $this->info('Orphaned objects (no database row): '.count($report->orphanObjects));
        $this->info('Missing objects (row without a file): '.count($report->missingObjects));

        foreach ($report->orphanObjects as $key) {
            $this->line("orphan: {$key}");
        }
        foreach ($report->missingObjects as $key) {
            $this->warn("missing: {$key}");
        }

        return self::SUCCESS;
    }
}
