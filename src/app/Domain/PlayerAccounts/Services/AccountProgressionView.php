<?php

namespace App\Domain\PlayerAccounts\Services;

use App\Domain\GameAssets\Services\GameAssetCatalogue;
use Illuminate\Support\Str;

/**
 * Groups API progression by village and asset kind, filling configured Home Village slots with locked tiles.
 * Unknown API units remain visible after the configured units; excluded units stay hidden.
 *
 * @phpstan-type ProgressionUnit array{name: string, slug: string, level: int, maxLevel: int, maxed: bool, unlocked: bool, equipment: list<array<string, mixed>>}
 */
final class AccountProgressionView
{
    public const VILLAGES = ['home' => 'Home Village', 'builder' => 'Builder Base'];

    private const GROUPS = ['Heroes', 'Pets', 'Troops', 'Siege machines', 'Spells'];

    private const KIND_GROUPS = ['hero' => 'Heroes', 'pet' => 'Pets', 'siege' => 'Siege machines', 'spell' => 'Spells', 'troop' => 'Troops'];

    public function __construct(private readonly GameAssetCatalogue $catalogue) {}

    /**
     * @param  array<int, mixed>  $heroes
     * @param  array<int, mixed>  $troops
     * @param  array<int, mixed>  $spells
     * @param  array<int, mixed>  $equipment
     * @return array<string, array<string, list<ProgressionUnit>>> village key → group label → units
     */
    public function build(array $heroes, array $troops, array $spells, array $equipment): array
    {
        $excluded = array_flip(array_map(static fn (mixed $slug): string => Str::slug((string) $slug), (array) config('coc.excluded_units')));
        $ownedEquipment = [];
        foreach ($equipment as $raw) {
            if (is_array($raw)) {
                $unit = $this->unit($raw);
                $ownedEquipment[$unit['slug']] = $unit;
            }
        }
        $heroEquipment = $this->catalogue->heroEquipment();
        $order = $this->catalogue->progressionOrder();
        $configuredKinds = [];
        foreach ($order as $kind => $slugs) {
            foreach ($slugs as $slug) {
                $configuredKinds[$slug] = $kind;
            }
        }
        $groups = array_values(array_unique(array_merge(
            array_map(static fn (string $kind): string => self::KIND_GROUPS[$kind], array_keys($order)),
            self::GROUPS,
        )));

        $villages = array_fill_keys(array_keys(self::VILLAGES), array_fill_keys($groups, []));
        foreach (['Heroes' => $heroes, 'Troops' => $troops, 'Spells' => $spells] as $column => $rows) {
            foreach ($rows as $raw) {
                if (! is_array($raw)) {
                    continue;
                }
                $unit = $this->unit($raw);
                if (isset($excluded[$unit['slug']])) {
                    continue;
                }

                if ($column === 'Heroes') {
                    $unit['equipment'] = array_map(
                        fn (string $slug): array => $ownedEquipment[$slug] ?? $this->locked($slug),
                        $heroEquipment[$unit['slug']] ?? [],
                    );
                }

                $village = ($raw['village'] ?? 'home') === 'builderBase' ? 'builder' : 'home';
                $kind = $this->catalogue->kind($unit['slug']) ?? $configuredKinds[$unit['slug']] ?? null;
                if ($kind === 'guardian') {
                    continue;
                }
                $group = $column === 'Troops' && $kind !== null ? (self::KIND_GROUPS[$kind] ?? $column) : $column;
                $villages[$village][$group][] = $unit;
            }
        }

        foreach ($order as $kind => $slugs) {
            $group = self::KIND_GROUPS[$kind];
            $owned = array_column($villages['home'][$group], null, 'slug');
            $ordered = [];
            foreach ($slugs as $slug) {
                if (isset($excluded[$slug])) {
                    continue;
                }

                $ordered[] = $owned[$slug] ?? $this->locked($slug);
                unset($owned[$slug]);
            }
            $villages['home'][$group] = array_merge($ordered, array_values($owned));
        }

        return array_map(static fn (array $groups): array => array_filter($groups), $villages);
    }

    /**
     * @param  array<mixed>  $raw
     * @return ProgressionUnit
     */
    private function unit(array $raw): array
    {
        $name = (string) ($raw['name'] ?? 'Unknown');
        $level = (int) ($raw['level'] ?? 0);
        $max = (int) ($raw['maxLevel'] ?? 0);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'level' => $level,
            'maxLevel' => $max,
            'maxed' => $max > 0 && $level >= $max,
            'unlocked' => true,
            'equipment' => [],
        ];
    }

    /**
     * @return ProgressionUnit
     */
    private function locked(string $slug): array
    {
        return [
            'name' => $this->catalogue->name($slug),
            'slug' => $slug,
            'level' => 0,
            'maxLevel' => 0,
            'maxed' => false,
            'unlocked' => false,
            'equipment' => [],
        ];
    }
}
