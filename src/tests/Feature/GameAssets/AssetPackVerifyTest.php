<?php

use App\Domain\GameAssets\Services\AssetPackVerifier;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MediaTesting;

/** Publish a committed manifest + matching bucket objects, then mutate the bucket to test the audit. */
function seedPack(array $files): void
{
    $dir = sys_get_temp_dir().'/verify-'.uniqid();
    @mkdir($dir, 0777, true);

    $assets = [];
    foreach ($files as $key => $bytes) {
        Storage::disk('r2')->put("game/{$key}", $bytes);
        $assets[] = [
            'key' => $key, 'slug' => pathinfo($key, PATHINFO_FILENAME), 'name' => 'X',
            'category' => 'unit', 'sha256' => hash('sha256', $bytes), 'bytes' => strlen($bytes),
        ];
    }
    file_put_contents($dir.'/manifest.json', json_encode(['assets' => $assets]));
    config(['assets.pack_path' => $dir]);
}

beforeEach(function () {
    Storage::fake('r2');
    config(['assets.disk' => 'r2', 'assets.prefix' => 'game']);
});

it('passes when every object matches the manifest', function () {
    seedPack(['units/a.png' => MediaTesting::pngBytes()]);

    $report = app(AssetPackVerifier::class)->verify();

    expect($report->ok())->toBeTrue()->and($report->checked)->toBe(1);
    $this->artisan('assets:verify-pack')
        ->expectsOutput('assets:verify-pack — game/ matches its manifest (1 assets).')
        ->assertSuccessful();
});

it('flags a modified object', function () {
    seedPack(['units/a.png' => MediaTesting::pngBytes()]);
    Storage::disk('r2')->put('game/units/a.png', MediaTesting::pngBytes(401, 301));

    $report = app(AssetPackVerifier::class)->verify();

    expect($report->ok())->toBeFalse()->and($report->modified)->toBe(['game/units/a.png']);
});

it('flags a missing object and an extra object', function () {
    seedPack(['units/a.png' => MediaTesting::pngBytes()]);
    Storage::disk('r2')->delete('game/units/a.png');
    Storage::disk('r2')->put('game/units/stowaway.png', 'not in the manifest');

    $report = app(AssetPackVerifier::class)->verify();

    expect($report->missing)->toBe(['game/units/a.png'])
        ->and($report->extra)->toBe(['game/units/stowaway.png']);
    $this->artisan('assets:verify-pack')
        ->expectsOutput('extra: game/units/stowaway.png')
        ->assertFailed();
});
