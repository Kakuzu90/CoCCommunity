<?php

use App\Domain\GameAssets\Contracts\GameAssetResolver;
use App\Domain\GameAssets\Exceptions\AssetPackException;
use App\Domain\GameAssets\Services\AssetPackPublisher;
use App\Domain\GameAssets\Services\ManifestGameAssetResolver;
use App\Domain\GameAssets\Services\ManifestReader;
use App\Domain\Media\Services\StorageReconciler;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MediaTesting;

/*
 * These assert the fan-content policy in executable form (specs/18 §2, specs/10 §11): revocable
 * (kill switch), unmodified (byte-exact), and unreachable by any sweeper or user upload path.
 */

it('serves no game asset when fan-content permission is revoked (kill switch)', function () {
    config([
        'assets.enabled' => false,
        'assets.pack_path' => sys_get_temp_dir().'/no-pack-'.uniqid(), // manifest deliberately absent — the switch must short-circuit first
    ]);
    $resolver = new ManifestGameAssetResolver(new ManifestReader);

    expect($resolver->enabled())->toBeFalse()
        ->and($resolver->unit('barbarian')->url)->toBeNull()
        ->and($resolver->townHall(15)->url)->toBeNull()
        ->and($resolver->league(29000022, 'Legend')->url)->toBeNull()
        ->and($resolver->clanBadge('https://api.example/badge.png', 'Zulu')->url)->toBeNull();
});

it('exposes no upload or presign path for the game/ prefix', function () {
    $methods = array_map(
        fn ($m) => $m->getName(),
        (new ReflectionClass(GameAssetResolver::class))->getMethods()
    );

    // The resolver only ever produces read-only public URLs; nothing here can mint a writable key.
    expect($methods)->toBe(['unit', 'townHall', 'league', 'clanBadge', 'enabled']);
});

it('keeps the game/ prefix outside every reconcile and sweep', function () {
    expect(StorageReconciler::PREFIXES)->not->toContain('game');
});

it('refuses to publish a modified (non-byte-exact) asset', function () {
    Storage::fake('r2');
    config(['assets.disk' => 'r2', 'assets.prefix' => 'game']);

    $dir = sys_get_temp_dir().'/sec-pack-'.uniqid();
    @mkdir($dir.'/units', 0777, true);
    $bytes = MediaTesting::pngBytes();
    file_put_contents($dir.'/units/barbarian.png', $bytes);
    file_put_contents($dir.'/manifest.json', json_encode(['assets' => [[
        'key' => 'units/barbarian.png', 'slug' => 'barbarian', 'name' => 'Barbarian',
        'category' => 'unit', 'sha256' => hash('sha256', $bytes), 'bytes' => strlen($bytes),
    ]]]));

    // Simulate an optimiser rewriting the file after the manifest was cut.
    file_put_contents($dir.'/units/barbarian.png', MediaTesting::pngBytes(401, 301));

    expect(fn () => app(AssetPackPublisher::class)->publish($dir))
        ->toThrow(AssetPackException::class);
    expect(Storage::disk('r2')->allFiles('game'))->toBe([]);
});
