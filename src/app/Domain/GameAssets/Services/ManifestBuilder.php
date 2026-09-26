<?php

namespace App\Domain\GameAssets\Services;

use App\Domain\GameAssets\Exceptions\AssetPackException;
use Illuminate\Support\Str;

/**
 * Generates the pack `manifest.json` from the files on disk (specs/10 §11.2 step 2). Checksums and
 * byte sizes are always recomputed; the curated fields (slug, name, village, source) of an entry
 * that already exists are kept, so hand-matched API names survive a rebuild. A new file gets
 * derived defaults, and an entry whose file is gone is dropped.
 */
final class ManifestBuilder
{
    private const KEY_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*\.(?:png|webp|jpe?g)$/';

    public function __construct(private readonly ManifestReader $reader) {}

    /**
     * @return array{assets: list<array<string, mixed>>}
     */
    public function build(string $packDir): array
    {
        $packDir = rtrim($packDir, '/');
        $existing = is_file($packDir.'/manifest.json')
            ? $this->byKey($this->reader->read($packDir.'/manifest.json')['assets'])
            : [];

        /** @var array<string, array{category: string, kind: string}> $directories */
        $directories = (array) config('assets.directories');

        $assets = [];
        $seen = [];
        foreach ($this->files($packDir) as $key) {
            [$directory, $file] = explode('/', $key, 2);
            $mapping = $directories[$directory] ?? throw new AssetPackException("Unknown pack directory '{$directory}' for '{$key}'.");
            if (preg_match(self::KEY_PATTERN, $file) !== 1) {
                throw new AssetPackException("File name '{$key}' must be lowercase kebab-case (rename the file, never re-encode it).");
            }

            $previous = $existing[$key] ?? [];
            $slug = (string) ($previous['slug'] ?? $this->defaultSlug($mapping['kind'], pathinfo($file, PATHINFO_FILENAME)));
            $lookup = $mapping['category'].':'.$slug;
            if (isset($seen[$lookup])) {
                throw new AssetPackException("Duplicate {$mapping['category']} slug '{$slug}' in '{$seen[$lookup]}' and '{$key}'.");
            }
            $seen[$lookup] = $key;

            $local = $packDir.'/'.$key;
            $assets[] = [
                'key' => $key,
                'slug' => $slug,
                'name' => (string) ($previous['name'] ?? $this->defaultName($mapping['kind'], $slug)),
                'category' => $mapping['category'],
                'kind' => $mapping['kind'],
                'village' => (string) ($previous['village'] ?? 'home'),
                'source' => (string) ($previous['source'] ?? 'staff-curated'),
                'sha256' => (string) hash_file('sha256', $local),
                'bytes' => (int) filesize($local),
            ];
        }

        return ['assets' => $assets];
    }

    /** @param array<string, mixed> $manifest */
    public function encode(array $manifest): string
    {
        return json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
    }

    /** @return list<string> keys relative to the pack directory, sorted */
    private function files(string $packDir): array
    {
        $keys = [];
        foreach (glob($packDir.'/*/*') ?: [] as $path) {
            if (is_file($path)) {
                $keys[] = substr($path, strlen($packDir) + 1);
            }
        }
        sort($keys);

        return $keys;
    }

    /**
     * @param  array<int, array<string, mixed>>  $assets
     * @return array<string, array<string, mixed>>
     */
    private function byKey(array $assets): array
    {
        $byKey = [];
        foreach ($assets as $entry) {
            $byKey[(string) $entry['key']] = $entry;
        }

        return $byKey;
    }

    /** Slugs mirror `Str::slug()` of the API name, which is how progression rows look assets up. */
    private function defaultSlug(string $kind, string $filename): string
    {
        $slug = Str::slug($filename);

        return $kind === 'spell' && ! str_ends_with($slug, '-spell') ? "{$slug}-spell" : $slug;
    }

    private function defaultName(string $kind, string $slug): string
    {
        $name = ucwords(str_replace('-', ' ', $slug));

        return match ($kind) {
            'townhall' => "Town Hall {$slug}",
            'league' => "{$name} League",
            default => $name,
        };
    }
}
