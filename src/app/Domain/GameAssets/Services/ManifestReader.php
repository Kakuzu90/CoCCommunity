<?php

namespace App\Domain\GameAssets\Services;

use App\Domain\GameAssets\Exceptions\AssetPackException;

/**
 * Reads and validates a pack `manifest.json` (specs/10 §11.2). The manifest records, per asset:
 * key, slug, display name, category, village, source, SHA-256 and byte size — so "unmodified" is
 * demonstrable, not asserted. The committed copy is what the resolver reads at runtime; the bucket
 * copy is what `assets:verify-pack` audits against.
 *
 * @phpstan-type ManifestEntry array{key: string, slug: string, name: string, category: string, village?: string, source?: string, sha256: string, bytes: int}
 */
final class ManifestReader
{
    /**
     * @return array{version: int, assets: array<int, array<string, mixed>>}
     */
    public function read(string $file): array
    {
        if (! is_file($file)) {
            throw new AssetPackException("Manifest not found: {$file}");
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        if (! is_array($decoded) || ! isset($decoded['assets']) || ! is_array($decoded['assets'])) {
            throw new AssetPackException("Manifest is malformed: {$file}");
        }

        $assets = [];
        foreach ($decoded['assets'] as $i => $entry) {
            if (! is_array($entry)) {
                throw new AssetPackException("Manifest entry {$i} is not an object.");
            }
            foreach (['key', 'slug', 'name', 'category', 'sha256', 'bytes'] as $required) {
                if (! array_key_exists($required, $entry)) {
                    throw new AssetPackException("Manifest entry {$i} is missing '{$required}'.");
                }
            }
            $assets[] = $entry;
        }

        return [
            'version' => (int) ($decoded['version'] ?? 0),
            'assets' => $assets,
        ];
    }

    /**
     * Index assets by "category:slug" for O(1) resolver lookups.
     *
     * @param  array<int, array<string, mixed>>  $assets
     * @return array<string, array<string, mixed>>
     */
    public function index(array $assets): array
    {
        $byKey = [];
        foreach ($assets as $entry) {
            $byKey[$entry['category'].':'.$entry['slug']] = $entry;
        }

        return $byKey;
    }
}
