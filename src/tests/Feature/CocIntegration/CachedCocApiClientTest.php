<?php

use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\Data\PlayerData;
use App\Domain\CocIntegration\Data\TokenVerificationResult;
use App\Domain\CocIntegration\Enums\CocErrorReason;
use App\Domain\CocIntegration\Enums\CocRequestPriority;
use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Domain\CocIntegration\Http\CachedCocApiClient;
use App\Support\ValueObjects\PlayerTag;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;

function spyInner(): CocApiClient
{
    return new class implements CocApiClient
    {
        public int $playerCalls = 0;

        public int $verifyCalls = 0;

        public string $mode = 'ok';

        public function player(PlayerTag $tag, CocRequestPriority $priority = CocRequestPriority::Interactive): PlayerData
        {
            $this->playerCalls++;

            return match ($this->mode) {
                'notfound' => throw CocApiException::notFound($tag->value),
                'server' => throw new CocApiException(CocErrorReason::ServerError, 'boom', 500),
                default => PlayerData::fromArray(['tag' => $tag->value, 'name' => 'Live', 'townHallLevel' => 15], CarbonImmutable::now()),
            };
        }

        public function verifyToken(PlayerTag $tag, string $token): TokenVerificationResult
        {
            $this->verifyCalls++;

            return TokenVerificationResult::ok($tag->value);
        }
    };
}

it('serves a cache hit without touching the inner client', function () {
    $inner = spyInner();
    $client = new CachedCocApiClient($inner, app(Cache::class));
    $tag = new PlayerTag('#2PP');

    $client->player($tag);
    $client->player($tag);

    expect($inner->playerCalls)->toBe(1);
});

it('negative-caches a not-found tag to stop retry storms', function () {
    $inner = spyInner();
    $inner->mode = 'notfound';
    $client = new CachedCocApiClient($inner, app(Cache::class));
    $tag = new PlayerTag('#2PP');

    expect(fn () => $client->player($tag))->toThrow(CocApiException::class);
    expect(fn () => $client->player($tag))->toThrow(CocApiException::class);

    expect($inner->playerCalls)->toBe(1); // second lookup answered from the negative cache
});

it('serves the last good copy, flagged stale, during a transient outage', function () {
    $inner = spyInner();
    $cache = app(Cache::class);
    $client = new CachedCocApiClient($inner, $cache);
    $tag = new PlayerTag('#2PP');

    $fresh = $client->player($tag);
    expect($fresh->stale)->toBeFalse();

    // Fresh entry expires but the 24h "last good" copy remains; the API then fails transiently.
    $cache->forget('coc:player:2PP');
    $inner->mode = 'server';

    $stale = $client->player($tag);

    expect($stale->stale)->toBeTrue()->and($stale->name)->toBe('Live');
});

it('rethrows a transient failure when there is no stale copy to serve', function () {
    $inner = spyInner();
    $inner->mode = 'server';
    $client = new CachedCocApiClient($inner, app(Cache::class));

    expect(fn () => $client->player(new PlayerTag('#2PP')))->toThrow(CocApiException::class);
});

it('never caches token verification — it must always be a live check', function () {
    $inner = spyInner();
    $client = new CachedCocApiClient($inner, app(Cache::class));
    $tag = new PlayerTag('#2PP');

    $client->verifyToken($tag, 'a');
    $client->verifyToken($tag, 'b');

    expect($inner->verifyCalls)->toBe(2);
});
