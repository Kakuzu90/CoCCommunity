<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

it('caches an ok storage result the health endpoint can read', function () {
    Storage::fake(config('health.storage_disk'));

    $this->artisan('platform:check-health')->assertExitCode(0);

    expect(Cache::get(config('health.cache.external')))
        ->toMatchArray(['status' => 'ok'])
        ->and(Cache::get(config('health.cache.external'))['at'])->not->toBeNull();
});

it('caches a down result and exits non-zero when storage is unreachable', function () {
    Storage::shouldReceive('disk')->andThrow(new RuntimeException('bucket unreachable'));

    $this->artisan('platform:check-health')->assertExitCode(1);

    expect(Cache::get(config('health.cache.external')))->toMatchArray(['status' => 'down']);
});
