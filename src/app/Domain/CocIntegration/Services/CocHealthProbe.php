<?php

namespace App\Domain\CocIntegration\Services;

use App\Domain\CocIntegration\KeyManagement\CocKeyPool;
use App\Domain\CocIntegration\Resilience\CircuitBreaker;
use App\Support\Health\HealthCheck;
use App\Support\Health\HealthStatus;

/**
 * Reports CoC integration readiness for the health surface (specs/09 §3 startup check, §6 key-pool
 * status). The scheduled platform:check-health command runs this and caches the result so the /health
 * endpoint never blocks. "All keys unhealthy" is the one Down state — it breaks every call and pages an
 * operator; an empty pool (local/fake) is Unknown, and an open circuit is Degraded, not Down.
 */
final readonly class CocHealthProbe
{
    public function __construct(
        private CocKeyPool $keys,
        private CircuitBreaker $breaker,
    ) {}

    public function check(): HealthCheck
    {
        $pool = $this->keys->snapshot();
        $circuit = $this->breaker->status();

        [$status, $message] = match (true) {
            $pool['total'] === 0 => [HealthStatus::Unknown, 'no keys configured'],
            $pool['healthy'] === 0 => [HealthStatus::Down, 'all keys unhealthy'],
            $circuit['open'] => [HealthStatus::Degraded, "circuit open for {$circuit['open_for']}s"],
            default => [HealthStatus::Ok, "{$pool['healthy']}/{$pool['total']} keys healthy"],
        };

        return new HealthCheck('coc_api', $status, $message, [
            'keys' => $pool,
            'circuit_open' => $circuit['open'],
        ]);
    }

    /** The startup readiness gate: at least one healthy key must exist (specs/09 §3). */
    public function hasHealthyKey(): bool
    {
        return $this->keys->healthyCount() > 0;
    }
}
