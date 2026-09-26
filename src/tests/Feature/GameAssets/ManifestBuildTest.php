<?php

use App\Domain\GameAssets\Exceptions\AssetPackException;
use App\Domain\GameAssets\Services\ManifestBuilder;
use Tests\Support\MediaTesting;

function rawPack(array $files): string
{
    $dir = sys_get_temp_dir().'/build-'.uniqid();
    foreach ($files as $key => $bytes) {
        @mkdir(dirname($dir.'/'.$key), 0777, true);
        file_put_contents($dir.'/'.$key, $bytes);
    }

    return $dir;
}

it('derives checksums, sizes and API-shaped slugs from the files', function () {
    $png = MediaTesting::pngBytes();
    $dir = rawPack(['units/hog-rider.png' => $png, 'spells/rage.png' => $png, 'townhalls/17.png' => $png, 'leagues/titan.png' => $png]);

    $assets = collect(app(ManifestBuilder::class)->build($dir)['assets'])->keyBy('key');

    expect($assets['units/hog-rider.png'])->toMatchArray([
        'slug' => 'hog-rider', 'name' => 'Hog Rider', 'category' => 'unit', 'kind' => 'troop',
        'sha256' => hash('sha256', $png), 'bytes' => strlen($png),
    ])
        ->and($assets['spells/rage.png'])->toMatchArray(['slug' => 'rage-spell', 'name' => 'Rage Spell', 'category' => 'unit'])
        ->and($assets['townhalls/17.png'])->toMatchArray(['slug' => '17', 'name' => 'Town Hall 17', 'category' => 'townhall'])
        ->and($assets['leagues/titan.png'])->toMatchArray(['slug' => 'titan', 'name' => 'Titan League', 'category' => 'league']);
});

it('keeps curated names on rebuild but refreshes checksums and drops removed files', function () {
    $dir = rawPack(['units/pekka.png' => MediaTesting::pngBytes(), 'units/gone.png' => MediaTesting::pngBytes()]);
    $builder = app(ManifestBuilder::class);
    $manifest = $builder->build($dir);
    $manifest['assets'][1]['name'] = 'P.E.K.K.A';
    file_put_contents($dir.'/manifest.json', $builder->encode($manifest));

    unlink($dir.'/units/gone.png');
    $replacement = MediaTesting::pngBytes(300, 300);
    file_put_contents($dir.'/units/pekka.png', $replacement);

    $assets = $builder->build($dir)['assets'];

    expect($assets)->toHaveCount(1)
        ->and($assets[0]['name'])->toBe('P.E.K.K.A')
        ->and($assets[0]['sha256'])->toBe(hash('sha256', $replacement));
});

it('rejects file names that are not URL-safe kebab-case', function () {
    $dir = rawPack(['equipments/seeking shield.png' => MediaTesting::pngBytes()]);

    expect(fn () => app(ManifestBuilder::class)->build($dir))->toThrow(AssetPackException::class);
});

it('rejects an unknown pack directory', function () {
    $dir = rawPack(['decorations/flag.png' => MediaTesting::pngBytes()]);

    expect(fn () => app(ManifestBuilder::class)->build($dir))->toThrow(AssetPackException::class);
});

it('keeps the committed manifest in sync with the committed files', function () {
    $this->artisan('assets:build-manifest', ['--check' => true])->assertSuccessful();
});
