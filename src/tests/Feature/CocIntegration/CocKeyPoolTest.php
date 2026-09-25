<?php

use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Domain\CocIntegration\KeyManagement\CocKeyPool;
use Illuminate\Contracts\Cache\Repository as Cache;

function pool(array $tokens): CocKeyPool
{
    return new CocKeyPool($tokens, app(Cache::class));
}

it('spreads selection round-robin across healthy keys', function () {
    $p = pool(['a', 'b', 'c']);

    $ids = collect(range(1, 6))->map(fn () => $p->next()->id)->unique()->values();

    expect($ids)->toHaveCount(3); // all three keys are used
});

it('pulls an unhealthy key out of rotation and keeps serving the rest', function () {
    $p = pool(['a', 'b']);
    $first = $p->next();

    $p->markUnhealthy($first->id, 'invalid_ip');

    expect($p->healthyCount())->toBe(1)
        ->and($p->total())->toBe(2);

    foreach (range(1, 5) as $_) {
        expect($p->next()->id)->not->toBe($first->id);
    }
});

it('throws when no healthy key remains so the caller can degrade', function () {
    $p = pool(['a']);
    $only = $p->next();
    $p->markUnhealthy($only->id, 'access_denied');

    expect(fn () => $p->next())->toThrow(CocApiException::class);
    expect($p->healthyCount())->toBe(0);
});

it('recovers a key when marked healthy again', function () {
    $p = pool(['a']);
    $id = $p->next()->id;
    $p->markUnhealthy($id, 'invalid_ip');
    $p->markHealthy($id);

    expect($p->healthyCount())->toBe(1);
});

it('never exposes tokens in its snapshot, only digest ids and states', function () {
    $p = pool(['super-secret-token']);
    $snapshot = $p->snapshot();

    expect($snapshot['total'])->toBe(1)
        ->and($snapshot['healthy'])->toBe(1)
        ->and(json_encode($snapshot))->not->toContain('super-secret-token');
});
