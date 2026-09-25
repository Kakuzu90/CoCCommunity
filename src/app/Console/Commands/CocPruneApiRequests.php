<?php

namespace App\Console\Commands;

use App\Domain\CocIntegration\Resilience\RequestLogger;
use Illuminate\Console\Command;

/**
 * Prune the coc_api_requests observability log at its retention window (specs/09 §4). Scheduled daily.
 */
class CocPruneApiRequests extends Command
{
    protected $signature = 'coc:prune-api-requests';

    protected $description = 'Delete CoC API request-log rows older than the configured retention';

    public function handle(RequestLogger $log): int
    {
        $deleted = $log->prune((int) config('coc.request_log.retention_days'));
        $this->info("coc:prune-api-requests removed {$deleted} row(s).");

        return self::SUCCESS;
    }
}
