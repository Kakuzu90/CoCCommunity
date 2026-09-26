<?php

namespace App\Domain\GameAssets\Services;

use App\Domain\GameAssets\Exceptions\AssetPackException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Unit kinds, names and configured progression/equipment order.
 * Missing manifests keep display fallbacks available.
 */
final class GameAssetCatalogue
{
    private const PROGRESSION_KINDS = ['heroes' => 'hero', 'units' => 'troop', 'spells' => 'spell', 'pets' => 'pet', 'siege-machines' => 'siege'];

    /** @var array<string, string>|null */
    private ?array $kinds = null;

    /** @var array<string, string> */
    private array $names = [];

    public function __construct(private readonly ManifestReader $reader) {}

    /** troop | hero | spell | pet | siege | equipment | guardian, or null when not in the pack. */
    public function kind(string $unitSlug): ?string
    {
        return $this->kinds()[$unitSlug] ?? null;
    }

    public function name(string $unitSlug): string
    {
        $this->kinds();

        return $this->names[$unitSlug] ?? ucwords(str_replace('-', ' ', $unitSlug));
    }

    /** @return array<string, list<string>> Asset kind to slugs, preserving configured section and item order. */
    public function progressionOrder(): array
    {
        $order = [];
        foreach ((array) config('assets') as $section => $values) {
            $kind = self::PROGRESSION_KINDS[$section] ?? null;
            if ($kind === null) {
                continue;
            }

            $slugs = array_map(static function (mixed $value) use ($kind): string {
                $slug = Str::slug(str_replace('_', '-', (string) $value));

                return $kind === 'spell' && $slug !== '' && ! str_ends_with($slug, '-spell') ? $slug.'-spell' : $slug;
            }, Arr::flatten((array) $values));
            $order[$kind] = array_values(array_unique(array_filter($slugs, static fn (string $slug): bool => $slug !== '')));
        }

        return $order;
    }

    /**
     * Hero slug → its equipment slugs, in configured order. Keys and values are normalised with
     * `Str::slug()` so `barbarian_king` and `eternal tome` match the API-derived slugs.
     *
     * @return array<string, list<string>>
     */
    public function heroEquipment(): array
    {
        $map = [];
        foreach ((array) config('assets.heroes_equipments') as $hero => $equipment) {
            $map[Str::slug(str_replace('_', '-', (string) $hero))] = array_values(array_map(
                static fn (mixed $slug): string => Str::slug((string) $slug),
                (array) $equipment,
            ));
        }

        return $map;
    }

    /** @return array<string, string> */
    private function kinds(): array
    {
        if ($this->kinds !== null) {
            return $this->kinds;
        }

        $this->kinds = [];
        try {
            $manifest = $this->reader->read(rtrim((string) config('assets.pack_path'), '/').'/manifest.json');
        } catch (AssetPackException) {
            return $this->kinds;
        }

        foreach ($manifest['assets'] as $entry) {
            if ($entry['category'] === 'unit' && isset($entry['kind'])) {
                $this->kinds[(string) $entry['slug']] = (string) $entry['kind'];
                $this->names[(string) $entry['slug']] = (string) $entry['name'];
            }
        }

        return $this->kinds;
    }
}
