<?php

use App\Domain\GameAssets\Services\ManifestGameAssetResolver;
use App\Domain\GameAssets\Services\ManifestReader;

/** Build a resolver over a throwaway manifest so we control the catalogue. */
function resolverWith(array $assets, bool $enabled = true): ManifestGameAssetResolver
{
    $dir = sys_get_temp_dir().'/ga-'.uniqid();
    @mkdir($dir, 0777, true);
    file_put_contents($dir.'/manifest.json', json_encode(['assets' => $assets]));

    config([
        'assets.enabled' => $enabled,
        'assets.pack_path' => $dir,
        'assets.prefix' => 'game',
        'assets.cdn_url' => 'https://cdn.test',
        'assets.cache_bust_length' => 12,
    ]);

    return new ManifestGameAssetResolver(new ManifestReader);
}

function manifestEntry(string $key, string $slug, string $name, string $category = 'unit', string $sha = 'abcdef0123456789abcd'): array
{
    return ['key' => $key, 'slug' => $slug, 'name' => $name, 'category' => $category, 'sha256' => $sha, 'bytes' => 1];
}

it('resolves a catalogued unit to its unmodified, checksum-busted URL', function () {
    $asset = resolverWith([manifestEntry('units/barbarian.png', 'barbarian', 'Barbarian')])->unit('barbarian');

    expect($asset->isPlaceholder())->toBeFalse()
        ->and($asset->url)->toBe('https://cdn.test/game/units/barbarian.png?v=abcdef012345')
        ->and($asset->name)->toBe('Barbarian');
});

it('falls back to a labelled placeholder for an unknown unit', function () {
    $asset = resolverWith([manifestEntry('units/barbarian.png', 'barbarian', 'Barbarian')])->unit('mystery-troop');

    expect($asset->isPlaceholder())->toBeTrue()
        ->and($asset->url)->toBeNull()
        ->and($asset->name)->toBe('Mystery Troop');
});

it('resolves a league tier to its family emblem by the API name', function () {
    $resolver = resolverWith([
        manifestEntry('leagues/pekka.png', 'pekka', 'P.E.K.K.A League', 'league'),
        manifestEntry('leagues/legend.png', 'legend', 'Legend League', 'league'),
    ]);

    expect($resolver->league(105000020, 'P.E.K.K.A League 20')->url)->toStartWith('https://cdn.test/game/leagues/pekka.png')
        ->and($resolver->league(105000020, 'P.E.K.K.A League 20')->name)->toBe('P.E.K.K.A League 20')
        ->and($resolver->league(105000034, 'Legend League')->url)->toStartWith('https://cdn.test/game/leagues/legend.png')
        ->and($resolver->league(105000035, 'Legend II')->url)->toStartWith('https://cdn.test/game/leagues/legend.png')
        ->and($resolver->league(105000035, 'Legend II')->name)->toBe('Legend II')
        ->and($resolver->league(105000034, 'Legend I')->isPlaceholder())->toBeFalse()
        ->and($resolver->league(105000036, 'Legend III')->isPlaceholder())->toBeFalse()
        ->and($resolver->league(105000035, 'Legend League II')->isPlaceholder())->toBeFalse()
        ->and($resolver->league(105000000, 'Unranked')->isPlaceholder())->toBeTrue()
        ->and($resolver->league(29000022, 'Crystal League I')->isPlaceholder())->toBeTrue();
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
    $resolver = resolverWith([manifestEntry('units/barbarian.png', 'barbarian', 'Barbarian')], enabled: false);

    expect($resolver->unit('barbarian')->isPlaceholder())->toBeTrue()
        ->and($resolver->townHall(15)->isPlaceholder())->toBeTrue()
        ->and($resolver->clanBadge('https://api-assets.example/badge.png', 'Reddit Zulu')->isPlaceholder())->toBeTrue();
});

it('resolves the committed pack for every catalogue the progression grid asks for', function () {
    config(['assets.enabled' => true, 'assets.pack_path' => resource_path('game-assets'), 'assets.cdn_url' => 'https://cdn.test']);
    $resolver = new ManifestGameAssetResolver(new ManifestReader);

    // Slugs are Str::slug() of the API names the progression grid receives.
    foreach (['barbarian', 'pekka', 'barbarian-king', 'healing-spell', 'lassi', 'wall-wrecker', 'metal-pants'] as $slug) {
        expect($resolver->unit($slug)->isPlaceholder())->toBeFalse("unit '{$slug}' should be in the pack");
    }
    foreach (range(1, 18) as $level) {
        expect($resolver->townHall($level)->isPlaceholder())->toBeFalse("Town Hall {$level} should be in the pack");
    }
    expect($resolver->league(1, 'Valkyrie League 14')->isPlaceholder())->toBeFalse()
        ->and($resolver->league(105000035, 'Legend II')->url)->toStartWith('https://cdn.test/game/leagues/legend.png')
        ->and($resolver->unit('a-troop-from-next-update')->isPlaceholder())->toBeTrue();
});
