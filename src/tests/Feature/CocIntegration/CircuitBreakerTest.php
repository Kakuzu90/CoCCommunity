<?php

use App\Domain\CocIntegration\Resilience\CircuitBreaker;
use App\Support\Health\HealthStatus;
use Illuminate\Contracts\Cache\Repository as Cache;

function breaker(): CircuitBreaker
{
    return new CircuitBreaker(app(Cache::class));
}

it('is closed and allows requests by default', function () {
    expect(breaker()->allowsRequest())->toBeTrue();
});

it('opens after a run of consecutive failures and blocks calls', function () {
    config(['coc.circuit.threshold' => 3]);
    $b = breaker();

    $b->recordFailure();
    $b->recordFailure();
    expect($b->allowsRequest())->toBeTrue();

    $b->recordFailure(); // third trips it

    expect($b->isOpen())->toBeTrue()
        ->and($b->allowsRequest())->toBeFalse();
});

it('opens on a high error rate over the window once enough samples exist', function () {
    config([
        'coc.circuit.threshold' => 100, // do not trip by run
        'coc.circuit.min_samples' => 4,
        'coc.circuit.error_rate' => 0.5,
    ]);
    $b = breaker();

    $b->recordSuccess();
    $b->recordSuccess();
    expect($b->isOpen())->toBeFalse();

    $b->recordFailure();
    $b->recordFailure();
    $b->recordFailure(); // 3 failures / 5 samples = 0.6 >= 0.5

    expect($b->isOpen())->toBeTrue();
});

it('closes again when a probe succeeds', function () {
    config(['coc.circuit.threshold' => 2]);
    $b = breaker();
    $b->recordFailure();
    $b->recordFailure();
    expect($b->isOpen())->toBeTrue();

    $b->recordSuccess();

    expect($b->isOpen())->toBeFalse()->and($b->allowsRequest())->toBeTrue();
});

it('opens explicitly for a maintenance window and reports the state', function () {
    $b = breaker();
    $b->open(1800);

    $status = $b->status();

    expect($b->isOpen())->toBeTrue()
        ->and($status['open'])->toBeTrue()
        ->and($status['status'])->toBe(HealthStatus::Degraded)
        ->and($status['open_for'])->toBeGreaterThan(0);
});
