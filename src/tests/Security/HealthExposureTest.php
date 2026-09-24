<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * The health endpoint is public (uptime monitors have no session), so it must expose only coarse
 * status — never configuration, secrets or stack traces (NFR-OBS-4, specs/11).
 */
it('is reachable without authentication', function () {
    $this->getJson('/health')->assertOk();
});

it('exposes only status, release and check names — no secrets or config', function () {
    $body = $this->getJson('/health')->getContent();

    expect($body)->not->toContain('secret')
        ->and($body)->not->toContain('password')
        ->and(strtolower($body))->not->toContain('app_key');

    $json = json_decode((string) $body, true);
    expect(array_keys($json))->toBe(['status', 'release', 'checks']);
});
