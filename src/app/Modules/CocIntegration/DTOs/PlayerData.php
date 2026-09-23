<?php

declare(strict_types=1);

namespace App\Modules\CocIntegration\DTOs;

use App\Modules\CocIntegration\Support\Tag;

/**
 * Internal representation of a Clash of Clans player, decoupled from the raw
 * API shape. `raw` preserves the full payload for the snapshot JSONB column.
 */
final readonly class PlayerData
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $tag,
        public string $name,
        public int $townHall,
        public int $expLevel,
        public int $trophies,
        public int $bestTrophies,
        public int $warStars,
        public ?string $league,
        public ?string $clanTag,
        public ?string $clanName,
        public ?string $clanRole,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            tag: Tag::normalize((string) ($data['tag'] ?? '')),
            name: (string) ($data['name'] ?? ''),
            townHall: (int) ($data['townHallLevel'] ?? 0),
            expLevel: (int) ($data['expLevel'] ?? 0),
            trophies: (int) ($data['trophies'] ?? 0),
            bestTrophies: (int) ($data['bestTrophies'] ?? 0),
            warStars: (int) ($data['warStars'] ?? 0),
            league: $data['league']['name'] ?? null,
            clanTag: $data['clan']['tag'] ?? null,
            clanName: $data['clan']['name'] ?? null,
            clanRole: $data['role'] ?? null,
            raw: $data,
        );
    }
}
