<?php

namespace App\Domain\GameAssets\Services;

use App\Domain\GameAssets\Exceptions\AssetPackException;
use Illuminate\Support\Str;

/**
 * What the pack knows about a unit beyond its artwork: its kind (the API lists pets and siege
 * machines inside `troops`) and which equipment belongs to which hero. Read from the committed
 * manifest and `config('assets.heroes_equipments')`; a missing manifest means "unknown", never an error.
 */
final class GameAssetCatalogue
{
    /** @var array<string, string>|null */
    private ?array $kinds = null;

    public function __construct(private readonly ManifestReader $reader) {}

    /** troop | hero | spell | pet | siege | equipment | guardian, or null when not in the pack. */
    public function kind(string $unitSlug): ?string
    {
        return $this->kinds()[$unitSlug] ?? null;
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
            }
        }

        return $this->kinds;
    }
}
