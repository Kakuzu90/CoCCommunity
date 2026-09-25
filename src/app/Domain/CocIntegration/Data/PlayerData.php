<?php

namespace App\Domain\CocIntegration\Data;

use Carbon\CarbonImmutable;

/**
 * A player, mapped from `/players/{tag}` into our own shape (specs/09 §8). Nothing downstream ever
 * sees a Supercell array key. Unit lists are plain arrays of {@see UnitData} so a game update adds
 * rows without a schema change. `stale` and `fetchedAt` are set by the caching decorator, not the API:
 * a stale payload is a successful past response served during an outage (specs/09 §5 stale-while-error).
 */
final readonly class PlayerData
{
    /**
     * @param  list<string>  $labels
     * @param  list<UnitData>  $heroes
     * @param  list<UnitData>  $troops
     * @param  list<UnitData>  $spells
     * @param  list<UnitData>  $heroEquipment
     */
    public function __construct(
        public string $tag,
        public string $name,
        public int $townHallLevel,
        public int $expLevel,
        public int $trophies,
        public int $bestTrophies,
        public int $warStars,
        public int $attackWins,
        public int $defenseWins,
        public int $donations,
        public int $donationsReceived,
        public ?LeagueData $league = null,
        public ?PlayerClanRef $clan = null,
        public array $labels = [],
        public array $heroes = [],
        public array $troops = [],
        public array $spells = [],
        public array $heroEquipment = [],
        public bool $stale = false,
        public ?CarbonImmutable $fetchedAt = null,
    ) {}

    /**
     * Map a raw `/players/{tag}` payload. Callers validate the shape first; this trusts it.
     *
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw, ?CarbonImmutable $fetchedAt = null): self
    {
        return new self(
            tag: (string) $raw['tag'],
            name: (string) ($raw['name'] ?? ''),
            townHallLevel: (int) ($raw['townHallLevel'] ?? 0),
            expLevel: (int) ($raw['expLevel'] ?? 0),
            trophies: (int) ($raw['trophies'] ?? 0),
            bestTrophies: (int) ($raw['bestTrophies'] ?? 0),
            warStars: (int) ($raw['warStars'] ?? 0),
            attackWins: (int) ($raw['attackWins'] ?? 0),
            defenseWins: (int) ($raw['defenseWins'] ?? 0),
            donations: (int) ($raw['donations'] ?? 0),
            donationsReceived: (int) ($raw['donationsReceived'] ?? 0),
            league: LeagueData::fromArray(is_array($raw['league'] ?? null) ? $raw['league'] : null),
            clan: PlayerClanRef::fromArray(is_array($raw['clan'] ?? null) ? $raw['clan'] : null),
            labels: self::labels($raw['labels'] ?? []),
            heroes: self::units($raw['heroes'] ?? []),
            troops: self::units($raw['troops'] ?? []),
            spells: self::units($raw['spells'] ?? []),
            heroEquipment: self::units($raw['heroEquipment'] ?? []),
            fetchedAt: $fetchedAt,
        );
    }

    /** Same values, flagged as a stale fallback served while the API is unreachable. */
    public function asStale(): self
    {
        if ($this->stale) {
            return $this;
        }

        return new self(
            $this->tag, $this->name, $this->townHallLevel, $this->expLevel, $this->trophies,
            $this->bestTrophies, $this->warStars, $this->attackWins, $this->defenseWins,
            $this->donations, $this->donationsReceived, $this->league, $this->clan, $this->labels,
            $this->heroes, $this->troops, $this->spells, $this->heroEquipment,
            stale: true, fetchedAt: $this->fetchedAt,
        );
    }

    /**
     * @return list<UnitData>
     */
    private static function units(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_map(
            static fn (array $unit): UnitData => UnitData::fromArray($unit),
            array_filter($raw, 'is_array'),
        ));
    }

    /**
     * @return list<string>
     */
    private static function labels(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $names = [];
        foreach ($raw as $label) {
            if (is_array($label) && isset($label['name'])) {
                $names[] = (string) $label['name'];
            }
        }

        return $names;
    }
}
