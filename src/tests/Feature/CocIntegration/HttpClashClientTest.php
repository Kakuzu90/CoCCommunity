<?php

declare(strict_types=1);

use App\Modules\CocIntegration\Adapters\HttpClashClient;
use App\Modules\CocIntegration\Exceptions\ClashApiUnavailableException;
use App\Modules\CocIntegration\Exceptions\PlayerNotFoundException;
use App\Modules\CocIntegration\Exceptions\RateLimitedException;
use Illuminate\Support\Facades\Http;

function client(): HttpClashClient
{
    return new HttpClashClient('https://api.test/v1', 'token', 10, 3, 0);
}

test('verifyToken posts the token and reads the status', function (): void {
    Http::fake(['*/verifytoken' => Http::response(['status' => 'ok'])]);

    expect(client()->verifyToken('#2P0YQRL8V', 'abc'))->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/players/%232P0YQRL8V/verifytoken')
        && $request['token'] === 'abc');
});

test('verifyToken returns false when the token is invalid', function (): void {
    Http::fake(['*/verifytoken' => Http::response(['status' => 'invalid'])]);

    expect(client()->verifyToken('#2P0YQRL8V', 'nope'))->toBeFalse();
});

test('fetchPlayer maps the payload into a PlayerData DTO', function (): void {
    Http::fake(['*/players/*' => Http::response([
        'tag' => '#2P0YQRL8V',
        'name' => 'NightWitch',
        'townHallLevel' => 17,
        'trophies' => 6000,
        'warStars' => 1500,
        'league' => ['name' => 'Legend League'],
        'clan' => ['tag' => '#CLAN', 'name' => 'Bicol Warriors'],
        'role' => 'coLeader',
    ])]);

    $player = client()->fetchPlayer('#2P0YQRL8V');

    expect($player->name)->toBe('NightWitch')
        ->and($player->townHall)->toBe(17)
        ->and($player->league)->toBe('Legend League')
        ->and($player->clanName)->toBe('Bicol Warriors');
});

test('it maps API status codes to typed exceptions', function (int $status, string $exception): void {
    Http::fake(['*/players/*' => Http::response([], $status)]);

    expect(fn () => client()->fetchPlayer('#2P0YQRL8V'))->toThrow($exception);
})->with([
    'not found' => [404, PlayerNotFoundException::class],
    'rate limited' => [429, RateLimitedException::class],
    'server error' => [503, ClashApiUnavailableException::class],
]);
