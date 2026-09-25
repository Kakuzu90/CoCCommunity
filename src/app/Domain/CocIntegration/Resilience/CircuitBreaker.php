<?php

namespace App\Domain\CocIntegration\Resilience;

use App\Support\Health\HealthStatus;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Shared circuit breaker for the CoC API (specs/09 §7). It opens after a run of consecutive failures
 * or a high error rate over a window, and while open no outbound call is made — everything serves from
 * cache/snapshots. Maintenance windows open it explicitly for the announced duration. State lives in
 * the cache so every worker sees the same circuit.
 */
final class CircuitBreaker
{
    public function __construct(private readonly Cache $cache) {}

    /** False while the circuit is open; a single probe is allowed once the open window elapses (half-open). */
    public function allowsRequest(): bool
    {
        return ! $this->isOpen();
    }

    public function isOpen(): bool
    {
        return $this->now() < (int) $this->cache->get('coc:circuit:open_until', 0);
    }

    public function recordSuccess(): void
    {
        // Reset the failure run and count the success as a window sample, but keep the rolling window
        // (it expires by TTL) so the error-rate trip stays meaningful. A successful probe closes it.
        $this->cache->forget('coc:circuit:consecutive');
        $this->bumpWindow('coc:circuit:samples', (int) config('coc.circuit.window'));
        $this->cache->forget('coc:circuit:open_until');
    }

    public function recordFailure(): void
    {
        $consecutive = (int) $this->cache->increment('coc:circuit:consecutive');

        $window = (int) config('coc.circuit.window');
        $samples = $this->bumpWindow('coc:circuit:samples', $window);
        $failures = $this->bumpWindow('coc:circuit:failures', $window);

        $tripByRun = $consecutive >= (int) config('coc.circuit.threshold');
        $tripByRate = $samples >= (int) config('coc.circuit.min_samples')
            && ($failures / $samples) >= (float) config('coc.circuit.error_rate');

        if ($tripByRun || $tripByRate) {
            $this->open((int) config('coc.circuit.probe_interval'));
        }
    }

    /**
     * Open the circuit for a fixed duration — a failure trip or an explicit maintenance window
     * (specs/09 §7). Opening clears the rolling counters so the post-probe state starts clean.
     */
    public function open(int $seconds): void
    {
        $this->cache->put('coc:circuit:open_until', $this->now() + $seconds, $seconds + 5);
        $this->cache->forget('coc:circuit:consecutive');
        $this->cache->forget('coc:circuit:samples');
        $this->cache->forget('coc:circuit:failures');
    }

    /** @return array{status: HealthStatus, open: bool, open_for: int} */
    public function status(): array
    {
        $openUntil = (int) $this->cache->get('coc:circuit:open_until', 0);
        $openFor = max(0, $openUntil - $this->now());

        return [
            'status' => $openFor > 0 ? HealthStatus::Degraded : HealthStatus::Ok,
            'open' => $openFor > 0,
            'open_for' => $openFor,
        ];
    }

    private function bumpWindow(string $key, int $window): int
    {
        $value = (int) $this->cache->get($key, 0);
        if ($value === 0) {
            $this->cache->put($key, 1, $window);

            return 1;
        }

        return (int) $this->cache->increment($key);
    }

    private function now(): int
    {
        return time();
    }
}
