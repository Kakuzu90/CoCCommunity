<?php

use App\Domain\GameAssets\Services\AssetPackVerifier;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MediaTesting;

/** Publish a committed manifest + matching bucket objects, then mutate the bucket to test the audit. */
function seedVersion(int $version, array $files): void
{
    $dir = sys_get_temp_dir().'/verify-'.uniqid();
    @mkdir($dir."/{$version}", 0777, true);

    $assets = [];
    foreach ($files as $key => $bytes) {
        Storage::disk('r2')->put("game/{$version}/{$key}", $bytes);
        $assets[] = [
            'key' => $key, 'slug' => pathinfo($key, PATHINFO_FILENAME), 'name' => 'X',
            'category' => 'unit', 'sha256' => hash('sha256', $bytes), 'bytes' => strlen($bytes),
        ];
    }
    file_put_contents($dir."/{$version}/manifest.json", json_encode(['version' => $version, 'assets' => $assets]));
    config(['assets.manifest_path' => $dir, 'assets.pack_version' => $version]);
}

beforeEach(function () {
    Storage::fake('r2');
    config(['assets.disk' => 'r2', 'assets.prefix' => 'game']);
});

it('identifies the active placeholder pack without claiming game assets were published', function () {
    config(['assets.manifest_path' => resource_path('game-assets'), 'assets.pack_version' => 1]);

    $this->artisan('assets:verify-pack')
        ->expectsOutput('assets:verify-pack — game/1/ is an empty placeholder pack (0 game assets published).')
        ->assertSuccessful();
});

it('still flags stray objects in the placeholder pack prefix', function () {
    config(['assets.manifest_path' => resource_path('game-assets'), 'assets.pack_version' => 1]);
    Storage::disk('r2')->put('game/1/units/stray.png', 'stray');

    $this->artisan('assets:verify-pack')
        ->expectsOutput('extra: game/1/units/stray.png')
        ->assertFailed();
});

it('passes when every object matches the manifest', function () {
    seedVersion(1, ['units/a.png' => MediaTesting::pngBytes()]);

    $report = app(AssetPackVerifier::class)->verify(1);

    expect($report->ok())->toBeTrue()->and($report->checked)->toBe(1);
});

it('flags a modified object', function () {
    seedVersion(1, ['units/a.png' => MediaTesting::pngBytes()]);
    Storage::disk('r2')->put('game/1/units/a.png', MediaTesting::pngBytes(401, 301));

    $report = app(AssetPackVerifier::class)->verify(1);

    expect($report->ok())->toBeFalse()->and($report->modified)->toBe(['game/1/units/a.png']);
});

it('flags a missing object and an extra object', function () {
    seedVersion(1, ['units/a.png' => MediaTesting::pngBytes()]);
    Storage::disk('r2')->delete('game/1/units/a.png');
    Storage::disk('r2')->put('game/1/units/stowaway.png', 'not in the manifest');

    $report = app(AssetPackVerifier::class)->verify(1);

    expect($report->missing)->toBe(['game/1/units/a.png'])
        ->and($report->extra)->toBe(['game/1/units/stowaway.png']);
});
