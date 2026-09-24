<?php

use App\Domain\GameAssets\Services\ManifestGameAssetResolver;
use App\Domain\GameAssets\Services\ManifestReader;

/** Build a resolver over a throwaway manifest so we control the catalogue. */
function resolverWith(array $assets, bool $enabled = true): ManifestGameAssetResolver
{
    $dir = sys_get_temp_dir().'/ga-'.uniqid();
    @mkdir($dir.'/7', 0777, true);
    file_put_contents($dir.'/7/manifest.json', json_encode(['version' => 7, 'assets' => $assets]));

    config([
        'assets.enabled' => $enabled,
        'assets.manifest_path' => $dir,
        'assets.pack_version' => 7,
        'assets.prefix' => 'game',
        'assets.cdn_url' => 'https://cdn.test',
    ]);

    return new ManifestGameAssetResolver(new ManifestReader);
}

it('resolves a catalogued unit to its versioned, unmodified URL', function () {
    $resolver = resolverWith([
        ['key' => 'units/barbarian.png', 'slug' => 'barbarian', 'name' => 'Barbarian', 'category' => 'unit', 'sha256' => 'x', 'bytes' => 1],
    ]);

    $asset = $resolver->unit('barbarian');

    expect($asset->isPlaceholder())->toBeFalse()
        ->and($asset->url)->toBe('https://cdn.test/game/7/units/barbarian.png')
        ->and($asset->name)->toBe('Barbarian');
});

it('falls back to a labelled placeholder for an unknown unit', function () {
    $asset = resolverWith([])->unit('mystery-troop');

    expect($asset->isPlaceholder())->toBeTrue()
        ->and($asset->url)->toBeNull()
        ->and($asset->name)->toBe('Mystery Troop');
});

it('always carries a name for Town Halls and leagues even without a pack', function () {
    $resolver = resolverWith([]);

    expect($resolver->townHall(15)->name)->toBe('Town Hall 15')
        ->and($resolver->townHall(15)->isPlaceholder())->toBeTrue()
        ->and($resolver->league(29000022, 'Legend League')->name)->toBe('Legend League');
});

it('passes through clan badges from the API URL without mirroring them', function () {
    $asset = resolverWith([])->clanBadge('https://api-assets.example/badge.png', 'Reddit Zulu');

    expect($asset->url)->toBe('https://api-assets.example/badge.png')
        ->and($asset->name)->toBe('Reddit Zulu clan badge');
});

it('returns placeholders everywhere when the kill switch is off', function () {
    $resolver = resolverWith([
        ['key' => 'units/barbarian.png', 'slug' => 'barbarian', 'name' => 'Barbarian', 'category' => 'unit', 'sha256' => 'x', 'bytes' => 1],
    ], enabled: false);

    expect($resolver->unit('barbarian')->isPlaceholder())->toBeTrue()
        ->and($resolver->townHall(15)->isPlaceholder())->toBeTrue()
        ->and($resolver->clanBadge('https://api-assets.example/badge.png', 'Reddit Zulu')->isPlaceholder())->toBeTrue();
});
