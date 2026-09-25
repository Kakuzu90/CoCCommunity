<?php

use App\Domain\CocIntegration\Http\HttpCocApiClient;
use App\Domain\CocIntegration\Http\ThrottledCocApiClient;
use App\Domain\CocIntegration\KeyManagement\CocKeyPool;
use App\Domain\CocIntegration\Resilience\CircuitBreaker;
use App\Domain\CocIntegration\Resilience\RequestLogger;
use App\Support\ValueObjects\PlayerTag;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

const SECRET_KEY = 'secret-api-key-abc123';
const SECRET_TOKEN = 'super-secret-in-game-token-xyz';

function fullStack(): ThrottledCocApiClient
{
    $pool = new CocKeyPool([SECRET_KEY], app(Cache::class));
    $http = new HttpCocApiClient(app(Factory::class), $pool);

    return new ThrottledCocApiClient($http, new CircuitBreaker(app(Cache::class)), new RequestLogger);
}

it('sends the in-game token live but never persists it to the request log', function () {
    Http::fake(['*/verifytoken' => Http::response(['tag' => '#2PP', 'status' => 'ok'], 200)]);

    fullStack()->verifyToken(new PlayerTag('#2PP'), SECRET_TOKEN);

    // The token did reach the API (it is a live check)...
    Http::assertSent(fn ($request) => ($request['token'] ?? null) === SECRET_TOKEN);

    // ...but no row of the observability log holds the token or the API key.
    $rows = DB::table('coc_api_requests')->get();
    expect($rows)->not->toBeEmpty();
    foreach ($rows as $row) {
        $serialized = json_encode($row);
        expect($serialized)->not->toContain(SECRET_TOKEN)
            ->and($serialized)->not->toContain(SECRET_KEY);
    }
});

it('logs a call by endpoint and status, storing no request body column', function () {
    Http::fake(['*/verifytoken' => Http::response(['tag' => '#2PP', 'status' => 'invalid'], 200)]);

    fullStack()->verifyToken(new PlayerTag('#2PP'), SECRET_TOKEN);

    $this->assertDatabaseHas('coc_api_requests', ['endpoint' => 'verifytoken', 'method' => 'POST', 'status' => 200]);
    expect(DB::getSchemaBuilder()->getColumnListing('coc_api_requests'))
        ->not->toContain('token')
        ->not->toContain('body');
});

it('identifies keys by a non-reversible digest, never the token itself', function () {
    $pool = new CocKeyPool([SECRET_KEY], app(Cache::class));
    $key = $pool->next();

    expect($key->id)->not->toBe(SECRET_KEY)
        ->and(strlen($key->id))->toBe(8)
        ->and(json_encode($pool->snapshot()))->not->toContain(SECRET_KEY);
});
