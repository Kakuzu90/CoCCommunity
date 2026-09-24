<?php

namespace App\Domain\GameAssets\Services;

use App\Domain\GameAssets\Contracts\GameAssetResolver;
use App\Domain\GameAssets\Data\GameAsset;
use App\Domain\GameAssets\Enums\GameAssetCategory;

/**
 * Resolves game assets from the committed manifest for the active pack version, with our own
 * placeholder as the always-available fallback (specs/18 §2.3, specs/09 §8). It reads the repo
 * manifest once per instance — bound as a singleton, so there is no per-request bucket listing.
 *
 * The kill switch short-circuits every path: `config('assets.enabled') = false` returns a
 * placeholder for the whole category, which is how "fan content permission is revocable" is
 * expressed in code (specs/10 §11.3).
 */
final class ManifestGameAssetResolver implements GameAssetResolver
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $index = null;

    public function __construct(private readonly ManifestReader $reader) {}

    public function unit(string $slug, ?string $name = null): GameAsset
    {
        return $this->catalogue(GameAssetCategory::Unit, $slug, $name ?? $this->humanise($slug), $name ?? 'Unknown unit');
    }

    public function townHall(int $level): GameAsset
    {
        return $this->catalogue(GameAssetCategory::TownHall, (string) $level, "Town Hall {$level}", "Town Hall {$level}");
    }

    public function league(int $leagueId, ?string $name = null): GameAsset
    {
        return $this->catalogue(GameAssetCategory::League, (string) $leagueId, $name, $name ?? 'Unranked');
    }

    public function clanBadge(?string $badgeUrl, string $clanName): GameAsset
    {
        $name = "{$clanName} clan badge";

        // Pass-through of the stored API URL; the kill switch withdraws it like any game asset.
        if (! $this->enabled() || $badgeUrl === null || $badgeUrl === '') {
            return new GameAsset('clan', $name);
        }

        return new GameAsset('clan', $name, $badgeUrl);
    }

    public function enabled(): bool
    {
        return (bool) config('assets.enabled');
    }

    /**
     * Manifest lookup for the static catalogue. Missing asset or kill switch → placeholder with the
     * label; nothing depends on an asset being present.
     */
    private function catalogue(GameAssetCategory $category, string $slug, ?string $preferredName, string $fallbackName): GameAsset
    {
        if (! $this->enabled()) {
            return new GameAsset($category->value, $preferredName ?? $fallbackName, null, $slug);
        }

        $entry = $this->index()[$category->value.':'.$slug] ?? null;
        if ($entry === null) {
            return new GameAsset($category->value, $preferredName ?? $fallbackName, null, $slug);
        }

        $name = $preferredName ?? (string) ($entry['name'] ?? $fallbackName);

        return new GameAsset($category->value, $name, $this->url((string) $entry['key']), $slug);
    }

    /** {cdn}/{prefix}/{version}/{key} — the versioned, immutable public URL (specs/10 §11.4). */
    private function url(string $key): string
    {
        $cdn = rtrim((string) config('assets.cdn_url'), '/');
        $prefix = trim((string) config('assets.prefix'), '/');
        $version = (int) config('assets.pack_version');

        return "{$cdn}/{$prefix}/{$version}/".ltrim($key, '/');
    }

    /** @return array<string, array<string, mixed>> */
    private function index(): array
    {
        if ($this->index === null) {
            $version = (int) config('assets.pack_version');
            $file = rtrim((string) config('assets.manifest_path'), '/')."/{$version}/manifest.json";
            $manifest = $this->reader->read($file);
            $this->index = $this->reader->index($manifest['assets']);
        }

        return $this->index;
    }

    private function humanise(string $slug): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }
}
