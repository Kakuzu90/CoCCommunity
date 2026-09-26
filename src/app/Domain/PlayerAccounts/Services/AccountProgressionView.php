<?php

namespace App\Domain\PlayerAccounts\Services;

use App\Domain\GameAssets\Services\GameAssetCatalogue;
use Illuminate\Support\Str;

/**
 * Shapes stored API progression for display: split by village (the API tags every unit `home` or
 * `builderBase`), grouped the way the game's profile screen groups them, with hidden units removed
 * (`config('coc.excluded_units')`) and each hero carrying its own equipment.
 *
 * The API lists pets and siege machines inside `troops`, so the group comes from the pack's kind
 * for that slug; a unit the pack does not know yet stays in the column it came from.
 *
 * @phpstan-type ProgressionUnit array{name: string, slug: string, level: int, maxLevel: int, maxed: bool, unlocked: bool, equipment: list<array<string, mixed>>}
 */
final class AccountProgressionView
{
    public const VILLAGES = ['home' => 'Home Village', 'builder' => 'Builder Base'];

    private const GROUPS = ['Heroes', 'Pets', 'Troops', 'Siege machines', 'Spells', 'Guardians'];

    private const KIND_GROUPS = ['hero' => 'Heroes', 'pet' => 'Pets', 'siege' => 'Siege machines', 'spell' => 'Spells', 'guardian' => 'Guardians', 'troop' => 'Troops'];

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

        $villages = array_fill_keys(array_keys(self::VILLAGES), array_fill_keys(self::GROUPS, []));
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
                $kind = $this->catalogue->kind($unit['slug']);
                $group = $column === 'Troops' && $kind !== null ? (self::KIND_GROUPS[$kind] ?? $column) : $column;
                $villages[$village][$group][] = $unit;
            }
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
     * Configured equipment the player has not unlocked yet.
     *
     * @return ProgressionUnit
     */
    private function locked(string $slug): array
    {
        return [
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'level' => 0,
            'maxLevel' => 0,
            'maxed' => false,
            'unlocked' => false,
            'equipment' => [],
        ];
    }
}
