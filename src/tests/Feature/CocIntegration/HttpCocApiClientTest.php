<?php

use App\Domain\CocIntegration\Enums\CocErrorReason;
use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Domain\CocIntegration\Http\HttpCocApiClient;
use App\Domain\CocIntegration\KeyManagement\CocKeyPool;
use App\Support\ValueObjects\PlayerTag;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

function cocPool(array $tokens = ['tok-a']): CocKeyPool
{
    return new CocKeyPool($tokens, app(Cache::class));
}

function httpClient(?CocKeyPool $pool = null): HttpCocApiClient
{
    return new HttpCocApiClient(app(Factory::class), $pool ?? cocPool());
}

function playerPayload(): array
{
    return [
        'tag' => '#2PP', 'name' => 'Chief', 'townHallLevel' => 15, 'expLevel' => 220,
        'trophies' => 4200, 'bestTrophies' => 5000, 'warStars' => 1500,
        'attackWins' => 100, 'defenseWins' => 20, 'donations' => 500, 'donationsReceived' => 400,
        'league' => ['id' => 29000022, 'name' => 'Legend League', 'iconUrls' => ['medium' => 'https://api/league.png']],
        'clan' => ['tag' => '#ABC', 'name' => 'Warriors', 'role' => 'coLeader', 'badgeUrls' => ['medium' => 'https://api/badge.png']],
        'labels' => [['name' => 'Veteran'], ['name' => 'Clan Wars']],
        'heroes' => [['name' => 'Barbarian King', 'level' => 80, 'maxLevel' => 90, 'village' => 'home']],
        'troops' => [['name' => 'Root Rider', 'level' => 1, 'maxLevel' => 5]],
        'spells' => [],
        'heroEquipment' => [['name' => 'Barbarian Puppet', 'level' => 18, 'maxLevel' => 18]],
    ];
}

function caught(callable $fn): CocApiException
{
    try {
        $fn();
    } catch (CocApiException $e) {
        return $e;
    }

    throw new RuntimeException('Expected a CocApiException, none thrown.');
}

it('maps a raw player payload into our DTO, never exposing an API array key', function () {
    Http::fake(['*/players/*' => Http::response(playerPayload(), 200)]);

    $player = httpClient()->player(new PlayerTag('#2PP'));

    expect($player->name)->toBe('Chief')
        ->and($player->townHallLevel)->toBe(15)
        ->and($player->trophies)->toBe(4200)
        ->and($player->league?->name)->toBe('Legend League')
        ->and($player->clan?->badgeUrl)->toBe('https://api/badge.png')
        ->and($player->labels)->toContain('Veteran')
        ->and($player->heroes[0]->name)->toBe('Barbarian King')
        ->and($player->heroEquipment[0]->name)->toBe('Barbarian Puppet')
        ->and($player->stale)->toBeFalse();
});

it('URL-encodes the tag and sends the key as a bearer token', function () {
    Http::fake(['*/players/*' => Http::response(playerPayload(), 200)]);

    httpClient(cocPool(['secret-key']))->player(new PlayerTag('#2PP'));

    Http::assertSent(fn ($request) => str_contains($request->url(), '%232PP')
        && $request->hasHeader('Authorization', 'Bearer secret-key'));
});

it('returns ok and invalid token results without throwing', function () {
    Http::fake(['*/verifytoken' => Http::sequence()
        ->push(['tag' => '#2PP', 'status' => 'ok'], 200)
        ->push(['tag' => '#2PP', 'status' => 'invalid'], 200)]);

    $client = httpClient();

    expect($client->verifyToken(new PlayerTag('#2PP'), 'good')->ok)->toBeTrue()
        ->and($client->verifyToken(new PlayerTag('#2PP'), 'stale')->ok)->toBeFalse();
});

it('classifies a 404 as not found', function () {
    Http::fake(['*/players/*' => Http::response(['reason' => 'notFound'], 404)]);

    expect(caught(fn () => httpClient()->player(new PlayerTag('#2PP')))->reason)
        ->toBe(CocErrorReason::NotFound);
});

it('marks a key unhealthy on an IP failure and retries once with another key', function () {
    Http::fake(['*/players/*' => Http::sequence()
        ->push(['reason' => 'accessDenied.invalidIp'], 403)
        ->push(playerPayload(), 200)]);

    $pool = cocPool(['tok-a', 'tok-b']);
    $player = httpClient($pool)->player(new PlayerTag('#2PP'));

    expect($player->name)->toBe('Chief')
        ->and($pool->healthyCount())->toBe(1);
});

it('throws once the pool is exhausted of healthy keys', function () {
    Http::fake(['*/players/*' => Http::response(['reason' => 'accessDenied.invalidIp'], 403)]);

    $pool = cocPool(['only-key']);
    $e = caught(fn () => httpClient($pool)->player(new PlayerTag('#2PP')));

    expect($e->reason)->toBe(CocErrorReason::InvalidIp)
        ->and($pool->healthyCount())->toBe(0);
});

it('honours Retry-After on a 429', function () {
    Http::fake(['*/players/*' => Http::response(['reason' => 'throttled'], 429, ['Retry-After' => '30'])]);

    $e = caught(fn () => httpClient()->player(new PlayerTag('#2PP')));

    expect($e->reason)->toBe(CocErrorReason::Throttled)->and($e->retryAfter)->toBe(30);
});

it('classifies a 503 as maintenance', function () {
    Http::fake(['*/players/*' => Http::response(['reason' => 'inMaintenance'], 503)]);

    expect(caught(fn () => httpClient()->player(new PlayerTag('#2PP')))->reason)->toBe(CocErrorReason::Maintenance);
});

it('classifies a 500 as a server error', function () {
    Http::fake(['*/players/*' => Http::response([], 500)]);

    expect(caught(fn () => httpClient()->player(new PlayerTag('#2PP')))->reason)->toBe(CocErrorReason::ServerError);
});

it('treats a network timeout as a failure, not a crash', function () {
    Http::fake(fn () => throw new ConnectionException('timed out'));

    expect(caught(fn () => httpClient()->player(new PlayerTag('#2PP')))->reason)->toBe(CocErrorReason::Timeout);
});

it('treats a 200 with an unexpected shape as malformed and writes nothing partial', function () {
    Http::fake(['*/players/*' => Http::response(['name' => 'no tag here'], 200)]);

    expect(caught(fn () => httpClient()->player(new PlayerTag('#2PP')))->reason)->toBe(CocErrorReason::Malformed);
});
