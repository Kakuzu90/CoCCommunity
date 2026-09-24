<?php

namespace App\Domain\Media\Jobs;

use App\Domain\Media\Services\OrphanMediaSweeper;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Illuminate\Support\Facades\Log;

// Scheduled hourly sweep (specs/20 §Media). The MediaSweepOrphans command is the manual/dry-run
// entrypoint; both delegate to OrphanMediaSweeper. Idempotent.
class SweepOrphanMediaJob implements ShouldQueue
{
    use FoundationQueueable, Queueable;

    public function __construct()
    {
        $this->onQueue(QueueName::Low->value);
    }

    public function handle(OrphanMediaSweeper $sweeper): void
    {
        $deleted = $sweeper->sweep();

        Log::info('media:sweep-orphans deleted orphaned media', ['deleted' => $deleted]);
    }
}
