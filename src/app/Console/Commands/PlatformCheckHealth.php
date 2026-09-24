<?php

namespace App\Console\Commands;

use App\Support\Health\HealthReporter;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Probes external reachability (object storage now; the CoC API once it exists) and caches the
 * result for the /health endpoint to read (specs/20 §2, §3 platform:check-health, every 5 min).
 * Keeping the slow check off the request path is the point.
 */
class PlatformCheckHealth extends Command
{
    protected $signature = 'platform:check-health';

    protected $description = 'Probe object storage (and later the CoC API) and cache the result for the health endpoint';

    public function handle(HealthReporter $reporter): int
    {
        $storage = $reporter->probeStorage();

        Cache::put(
            (string) config('health.cache.external'),
            [
                'status' => $storage->status->value,
                'message' => $storage->message,
                'at' => Carbon::now()->toIso8601String(),
            ],
            (int) config('health.external_ttl'),
        );

        $this->info("platform:check-health storage={$storage->status->value} ({$storage->message})");

        return $storage->status->isFailure() ? self::FAILURE : self::SUCCESS;
    }
}
