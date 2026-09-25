<?php

use App\Domain\CocIntegration\Enums\CocErrorReason;
use App\Domain\CocIntegration\Enums\CocRequestPriority;
use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Domain\CocIntegration\Http\ThrottledCocApiClient;
use App\Domain\CocIntegration\Resilience\CircuitBreaker;
use App\Domain\CocIntegration\Resilience\RequestLogger;
use App\Domain\CocIntegration\Testing\FakeCocApiClient;
use App\Support\ValueObjects\PlayerTag;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function throttled(FakeCocApiClient $inner, ?CircuitBreaker $breaker = null): ThrottledCocApiClient
{
    return new ThrottledCocApiClient($inner, $breaker ?? new CircuitBreaker(app(Cache::class)), new RequestLogger);
}

$tag = fn () => new PlayerTag('#2PP');

it('records a successful call in the request log as a live, uncached call', function () use ($tag) {
    throttled(new FakeCocApiClient)->player($tag());

    $this->assertDatabaseHas('coc_api_requests', [
        'endpoint' => 'players', 'method' => 'GET', 'status' => 200, 'cached' => false, 'reason' => null,
    ]);
});

it('enforces the global per-second budget', function () use ($tag) {
    config(['coc.rate.global_per_second' => 2, 'coc.rate.global_per_minute' => 100]);
    $client = throttled(new FakeCocApiClient);

    $client->player($tag());
    $client->player($tag());

    expect(fn () => $client->player($tag()))
        ->toThrow(CocApiException::class);
});

it('caps background calls below the ceiling so interactive keeps its reserved share', function () use ($tag) {
    config(['coc.rate.global_per_second' => 2, 'coc.rate.global_per_minute' => 100, 'coc.rate.interactive_share' => 0.5]);
    $client = throttled(new FakeCocApiClient);

    // Background budget = floor(2 * 0.5) = 1: the second background call is refused...
    $client->player($tag(), CocRequestPriority::Background);
    expect(fn () => $client->player($tag(), CocRequestPriority::Background))->toThrow(CocApiException::class);

    // ...while interactive still has its own per-second budget.
    expect($client->player($tag(), CocRequestPriority::Interactive)->name)->not->toBeEmpty();
});

it('refuses to call while the circuit is open and logs it without a status', function () use ($tag) {
    $breaker = new CircuitBreaker(app(Cache::class));
    $breaker->open(60);

    $e = null;
    try {
        throttled(new FakeCocApiClient, $breaker)->player($tag());
    } catch (CocApiException $ex) {
        $e = $ex;
    }

    expect($e?->reason)->toBe(CocErrorReason::CircuitOpen);
    $this->assertDatabaseHas('coc_api_requests', ['reason' => 'circuit_open', 'status' => null]);
});

it('opens the circuit on a maintenance response', function () use ($tag) {
    $breaker = new CircuitBreaker(app(Cache::class));
    $inner = (new FakeCocApiClient)->throwFor('#2PP', CocApiException::maintenance(120));

    expect(fn () => throttled($inner, $breaker)->player($tag()))->toThrow(CocApiException::class);
    expect($breaker->isOpen())->toBeTrue();
});

it('treats throttling as normal and never trips the circuit on it', function () use ($tag) {
    config(['coc.circuit.threshold' => 2, 'coc.rate.global_per_second' => 100, 'coc.rate.global_per_minute' => 500]);
    $breaker = new CircuitBreaker(app(Cache::class));
    $inner = (new FakeCocApiClient)->throwFor('#2PP', CocApiException::throttled(5));
    $client = throttled($inner, $breaker);

    foreach (range(1, 5) as $_) {
        try {
            $client->player($tag());
        } catch (CocApiException) {
            // expected
        }
    }

    expect($breaker->isOpen())->toBeFalse();
});
