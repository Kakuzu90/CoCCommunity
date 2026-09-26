<?php

use App\Domain\GameAssets\Exceptions\AssetPackException;
use App\Domain\GameAssets\Services\AssetPackPublisher;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MediaTesting;

function packSource(array $files): string
{
    $dir = sys_get_temp_dir().'/pack-'.uniqid();

    $assets = [];
    foreach ($files as $key => $bytes) {
        $path = $dir.'/'.$key;
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, $bytes);
        $assets[] = [
            'key' => $key,
            'slug' => pathinfo($key, PATHINFO_FILENAME),
            'name' => ucfirst(pathinfo($key, PATHINFO_FILENAME)),
            'category' => str_starts_with($key, 'townhalls/') ? 'townhall' : 'unit',
            'village' => 'home',
            'source' => 'test-fixture',
            'sha256' => hash('sha256', $bytes),
            'bytes' => strlen($bytes),
        ];
    }
    file_put_contents($dir.'/manifest.json', json_encode(['assets' => $assets]));

    return $dir;
}

beforeEach(function () {
    Storage::fake('r2');
    config(['assets.disk' => 'r2', 'assets.prefix' => 'game']);
});

it('uploads the pack byte-for-byte and verifies each checksum', function () {
    $png = MediaTesting::pngBytes();
    $dir = packSource(['units/barbarian.png' => $png, 'townhalls/15.png' => MediaTesting::pngBytes(300, 300)]);

    $report = app(AssetPackPublisher::class)->publish($dir);

    expect($report->count())->toBe(2);
    Storage::disk('r2')->assertExists('game/units/barbarian.png');
    Storage::disk('r2')->assertExists('game/townhalls/15.png');
    // Byte-exact: what we uploaded is what we stored — no re-encode.
    expect(Storage::disk('r2')->get('game/units/barbarian.png'))->toBe($png);
});

it('publishes through the command from the configured pack path', function () {
    config(['assets.pack_path' => packSource(['units/barbarian.png' => MediaTesting::pngBytes()])]);

    $this->artisan('assets:publish-pack')
        ->expectsOutput('Published 1 assets to game/.')
        ->assertSuccessful();
    Storage::disk('r2')->assertExists('game/units/barbarian.png');
});

it('aborts when a file does not match its manifest checksum (tamper defence)', function () {
    $dir = packSource(['units/barbarian.png' => MediaTesting::pngBytes()]);
    // Tamper with the file after the manifest was written.
    file_put_contents($dir.'/units/barbarian.png', MediaTesting::pngBytes(401, 301));

    expect(fn () => app(AssetPackPublisher::class)->publish($dir))
        ->toThrow(AssetPackException::class);

    // Nothing was uploaded — the pre-flight check runs before any write.
    expect(Storage::disk('r2')->allFiles('game'))->toBe([]);
});

it('refuses an empty pack', function () {
    $dir = sys_get_temp_dir().'/pack-'.uniqid();
    @mkdir($dir, 0777, true);
    file_put_contents($dir.'/manifest.json', json_encode(['assets' => []]));

    expect(fn () => app(AssetPackPublisher::class)->publish($dir))
        ->toThrow(AssetPackException::class);
});
