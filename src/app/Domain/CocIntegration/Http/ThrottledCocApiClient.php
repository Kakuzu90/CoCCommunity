<?php

namespace App\Domain\CocIntegration\Http;

use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\Data\PlayerData;
use App\Domain\CocIntegration\Data\TokenVerificationResult;
use App\Domain\CocIntegration\Enums\CocErrorReason;
use App\Domain\CocIntegration\Enums\CocRequestPriority;
use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Domain\CocIntegration\Resilience\CircuitBreaker;
use App\Domain\CocIntegration\Resilience\RequestLogger;
use App\Support\ValueObjects\PlayerTag;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The decorator that keeps the app a good citizen of the API (specs/09 §4, §7): it self-limits well
 * below any observed ceiling, reserves headroom for interactive lookups, refuses to call while the
 * circuit is open, and records every call in coc_api_requests. Throttling (429) is treated as a normal
 * condition, not a failure that trips the breaker; server errors, timeouts and maintenance are.
 */
final class ThrottledCocApiClient implements CocApiClient
{
    public function __construct(
        private readonly CocApiClient $inner,
        private readonly CircuitBreaker $breaker,
        private readonly RequestLogger $log,
    ) {}

    public function player(PlayerTag $tag, CocRequestPriority $priority = CocRequestPriority::Interactive): PlayerData
    {
        return $this->guard('players', 'GET', $priority, fn (): PlayerData => $this->inner->player($tag, $priority));
    }

    public function verifyToken(PlayerTag $tag, string $token): TokenVerificationResult
    {
        // Verification is always user-driven and time-critical (specs/09 §9): interactive priority.
        return $this->guard(
            'verifytoken',
            'POST',
            CocRequestPriority::Interactive,
            fn (): TokenVerificationResult => $this->inner->verifyToken($tag, $token),
        );
    }

    /**
     * @template T
     *
     * @param  callable(): T  $call
     * @return T
     */
    private function guard(string $endpoint, string $method, CocRequestPriority $priority, callable $call): mixed
    {
        if (! $this->breaker->allowsRequest()) {
            $this->log->record($endpoint, $method, null, 0, false, reason: CocErrorReason::CircuitOpen->value);

            throw CocApiException::circuitOpen();
        }

        $this->reserveBudget($priority);

        $startedAt = microtime(true);

        try {
            $result = $call();
        } catch (CocApiException $e) {
            $this->onFailure($e);
            $this->log->record($endpoint, $method, $e->httpStatus, $this->elapsed($startedAt), false, reason: $e->reason->value);

            throw $e;
        }

        $this->breaker->recordSuccess();
        $this->log->record($endpoint, $method, 200, $this->elapsed($startedAt), false);

        return $result;
    }

    private function onFailure(CocApiException $e): void
    {
        match ($e->reason) {
            // Maintenance is explicit: open the circuit for the announced (or default) duration.
            CocErrorReason::Maintenance => $this->breaker->open(
                $e->retryAfter ?? (int) config('coc.circuit.maintenance_default'),
            ),
            // Genuine outages count toward the breaker.
            CocErrorReason::ServerError, CocErrorReason::Timeout, CocErrorReason::Malformed => $this->breaker->recordFailure(),
            // A missing tag means the API is healthy; throttling and key faults are handled elsewhere.
            CocErrorReason::NotFound => $this->breaker->recordSuccess(),
            default => null,
        };
    }

    /**
     * Enforce the self-imposed budget (specs/09 §4). Background calls are capped below the per-second
     * ceiling so interactive lookups always keep their reserved share; the per-minute cap is shared.
     */
    private function reserveBudget(CocRequestPriority $priority): void
    {
        $perSecond = (int) config('coc.rate.global_per_second');
        $perMinute = (int) config('coc.rate.global_per_minute');
        $share = (float) config('coc.rate.interactive_share');

        $secondLimit = $priority === CocRequestPriority::Background
            ? (int) floor($perSecond * (1.0 - $share))
            : $perSecond;

        $this->hitOrThrow($priority->limiterKey().'-s', max(1, $secondLimit), 1);
        $this->hitOrThrow('coc-global-m', max(1, $perMinute), 60);
    }

    private function hitOrThrow(string $key, int $max, int $decaySeconds): void
    {
        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw CocApiException::throttled(RateLimiter::availableIn($key));
        }

        RateLimiter::hit($key, $decaySeconds);
    }

    private function elapsed(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
