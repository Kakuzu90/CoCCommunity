<?php

namespace App\Domain\CocIntegration\Data;

/**
 * One hero, troop, spell or piece of hero equipment (specs/09 §8). Unknown unit names are kept
 * verbatim, never dropped, so a game update never silently loses progression (specs/09 §8 resilience).
 */
final readonly class UnitData
{
    public function __construct(
        public string $name,
        public int $level,
        public int $maxLevel,
        public string $village = 'home',
        public bool $superTroopIsActive = false,
    ) {}

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            name: (string) ($raw['name'] ?? 'Unknown'),
            level: (int) ($raw['level'] ?? 0),
            maxLevel: (int) ($raw['maxLevel'] ?? 0),
            village: (string) ($raw['village'] ?? 'home'),
            superTroopIsActive: (bool) ($raw['superTroopIsActive'] ?? false),
        );
    }
}
