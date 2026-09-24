<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('reports readiness as JSON with a per-check breakdown', function () {
    $res = $this->getJson('/health');

    $res->assertOk()
        ->assertJsonPath('checks.database.status', 'ok')
        ->assertJsonStructure(['status', 'release', 'checks' => ['database', 'queue', 'storage']]);

    expect($res->headers->get('X-Request-Id'))->not->toBeNull();
});

it('marks storage unknown until the external check has run, without failing liveness', function () {
    $res = $this->getJson('/health');

    $res->assertOk()->assertJsonPath('checks.storage.status', 'unknown');
});

it('reflects a cached storage-down result but still serves 200 while the database is up', function () {
    Cache::put(config('health.cache.external'), ['status' => 'down', 'message' => 'unreachable', 'at' => now()->toIso8601String()], 360);

    $res = $this->getJson('/health');

    $res->assertOk()
        ->assertJsonPath('checks.storage.status', 'down')
        ->assertJsonPath('status', 'down');
});

it('reports storage ok after the external check has run', function () {
    Storage::fake(config('health.storage_disk'));

    $this->artisan('platform:check-health')->assertExitCode(0);

    $this->getJson('/health')->assertOk()->assertJsonPath('checks.storage.status', 'ok');
});
