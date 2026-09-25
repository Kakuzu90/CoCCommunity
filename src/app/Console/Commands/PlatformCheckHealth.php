<?php

namespace App\Console\Commands;

use App\Domain\CocIntegration\Services\CocHealthProbe;
use App\Support\Health\HealthCheck;
use App\Support\Health\HealthReporter;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Probes external reachability (object storage and the CoC key pool) and caches each result for the
 * /health endpoint to read (specs/20 §2, §3 platform:check-health, every 5 min; specs/09 §3 key-pool
 * status). Keeping the slow/stateful checks off the request path is the point.
 */
class PlatformCheckHealth extends Command
{
    protected $signature = 'platform:check-health';

    protected $description = 'Probe object storage and the CoC API key pool and cache the result for the health endpoint';

    public function handle(HealthReporter $reporter, CocHealthProbe $coc): int
    {
        $storage = $reporter->probeStorage();
        $this->cache((string) config('health.cache.external'), $storage, (int) config('health.external_ttl'));

        $cocCheck = $coc->check();
        $this->cache((string) config('health.cache.coc'), $cocCheck, (int) config('health.external_ttl'));

        $this->info("platform:check-health storage={$storage->status->value} coc={$cocCheck->status->value}");

        return $storage->status->isFailure() ? self::FAILURE : self::SUCCESS;
    }

    private function cache(string $key, HealthCheck $check, int $ttl): void
    {
        Cache::put($key, [
            'status' => $check->status->value,
            'message' => $check->message,
            'at' => Carbon::now()->toIso8601String(),
        ], $ttl);
    }
}
