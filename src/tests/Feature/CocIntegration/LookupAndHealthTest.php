<?php

use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\KeyManagement\CocKeyPool;
use App\Domain\CocIntegration\Resilience\CircuitBreaker;
use App\Domain\CocIntegration\Services\CocHealthProbe;
use App\Domain\CocIntegration\Services\PlayerLookup;
use App\Domain\CocIntegration\Services\TokenVerifier;
use App\Domain\CocIntegration\Testing\FakeCocApiClient;
use App\Support\Health\HealthStatus;
use App\Support\ValueObjects\PlayerTag;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('binds the fake client by default so lookups work with no key', function () {
    expect(app(CocApiClient::class))->toBeInstanceOf(FakeCocApiClient::class);

    $player = app(PlayerLookup::class)->find(new PlayerTag('#2PP'));
    $result = app(TokenVerifier::class)->verify(new PlayerTag('#2PP'), 'token');

    expect($player->townHallLevel)->toBeGreaterThan(0)
        ->and($result->ok)->toBeTrue();
});

it('reports the key pool through the health probe', function () {
    $breaker = new CircuitBreaker(app(Cache::class));

    $empty = new CocHealthProbe(new CocKeyPool([], app(Cache::class)), $breaker);
    expect($empty->check()->status)->toBe(HealthStatus::Unknown)
        ->and($empty->hasHealthyKey())->toBeFalse();

    $healthy = new CocHealthProbe(new CocKeyPool(['a', 'b'], app(Cache::class)), $breaker);
    expect($healthy->check()->status)->toBe(HealthStatus::Ok)
        ->and($healthy->hasHealthyKey())->toBeTrue();
});

it('marks the probe down when every key is unhealthy', function () {
    $pool = new CocKeyPool(['a'], app(Cache::class));
    $pool->markUnhealthy($pool->next()->id, 'invalid_ip');

    $probe = new CocHealthProbe($pool, new CircuitBreaker(app(Cache::class)));

    expect($probe->check()->status)->toBe(HealthStatus::Down);
});

it('passes the startup key check on the fake driver and fails it on http with no key', function () {
    config(['coc.driver' => 'fake']);
    $this->artisan('coc:verify-keys')->assertExitCode(0);

    config(['coc.driver' => 'http', 'coc.tokens' => []]);
    $this->artisan('coc:verify-keys')->assertExitCode(1);
});

it('prunes request-log rows past the retention window', function () {
    config(['coc.request_log.retention_days' => 7]);
    DB::table('coc_api_requests')->insert([
        ['endpoint' => 'players', 'method' => 'GET', 'status' => 200, 'duration_ms' => 5, 'cached' => false, 'created_at' => now()->subDays(10)],
        ['endpoint' => 'players', 'method' => 'GET', 'status' => 200, 'duration_ms' => 5, 'cached' => false, 'created_at' => now()->subDay()],
    ]);

    $this->artisan('coc:prune-api-requests')->assertExitCode(0);

    expect(DB::table('coc_api_requests')->count())->toBe(1);
});

it('caches a coc key-pool result the health endpoint exposes', function () {
    Storage::fake(config('health.storage_disk'));

    $this->artisan('platform:check-health')->assertExitCode(0);

    expect(app(Cache::class)->get(config('health.cache.coc')))->toHaveKey('status');

    $this->getJson('/health')->assertOk()->assertJsonPath('checks.coc_api.status', 'unknown');
});
