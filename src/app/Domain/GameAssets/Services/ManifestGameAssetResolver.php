<?php

namespace App\Domain\GameAssets\Services;

use App\Domain\GameAssets\Contracts\GameAssetResolver;
use App\Domain\GameAssets\Data\GameAsset;
use App\Domain\GameAssets\Enums\GameAssetCategory;
use Illuminate\Support\Str;

/**
 * Resolves game assets from the committed pack manifest, with our own placeholder as the
 * always-available fallback (specs/18 §2.3, specs/09 §8). It reads the repo manifest once per
 * instance — bound as a singleton, so there is no per-request bucket listing.
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

    /**
     * League emblems are one per league family ("Wizard League 12" → `wizard`); the tiers inside a
     * family share the artwork, so the lookup is by the API's name, with the id as the slug label.
     */
    public function league(int $leagueId, ?string $name = null): GameAsset
    {
        $family = $name === null ? (string) $leagueId : $this->leagueFamily($name);

        return $this->catalogue(GameAssetCategory::League, $family, $name, $name ?? 'Unranked');
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

        return new GameAsset($category->value, $name, $this->url((string) $entry['key'], (string) $entry['sha256']), $slug);
    }

    /**
     * {cdn}/{prefix}/{key}?v={sha prefix} — the checksum in the query string means a replaced file
     * gets a new URL, so immutable caching is safe without versioned prefixes (specs/10 §11.4).
     */
    private function url(string $key, string $sha256): string
    {
        $cdn = rtrim((string) config('assets.cdn_url'), '/');
        $prefix = trim((string) config('assets.prefix'), '/');
        $bust = substr($sha256, 0, (int) config('assets.cache_bust_length'));

        return "{$cdn}/{$prefix}/".ltrim($key, '/')."?v={$bust}";
    }

    /** @return array<string, array<string, mixed>> */
    private function index(): array
    {
        if ($this->index === null) {
            $manifest = $this->reader->read(rtrim((string) config('assets.pack_path'), '/').'/manifest.json');
            $this->index = $this->reader->index($manifest['assets']);
        }

        return $this->index;
    }

    /** "P.E.K.K.A League 20" → "pekka", "Legend League" → "legend". */
    private function leagueFamily(string $name): string
    {
        $slug = (string) preg_replace('/-\d+$/', '', Str::slug($name));

        return (string) preg_replace('/-league$/', '', $slug);
    }

    private function humanise(string $slug): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }
}
