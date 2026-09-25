<?php

namespace App\Domain\PlayerAccounts\Services;

use App\Domain\CocIntegration\Data\PlayerData;

final class AccountProgression
{
    /** @return array<string, mixed> */
    public static function fromPlayer(PlayerData $player): array
    {
        return [
            'ign' => $player->name,
            'th_level' => max(1, $player->townHallLevel),
            'builder_hall_level' => $player->builderHallLevel,
            'builder_trophies' => $player->builderTrophies,
            'xp_level' => $player->expLevel,
            'trophies' => $player->trophies,
            'best_trophies' => $player->bestTrophies,
            'war_stars' => $player->warStars,
            'attack_wins' => $player->attackWins,
            'defense_wins' => $player->defenseWins,
            'donations' => $player->donations,
            'donations_received' => $player->donationsReceived,
            'clan_tag' => $player->clan?->tag,
            'clan_role' => $player->clan?->role,
            'league_id' => $player->league?->id,
            'league_name' => $player->league?->name,
            'league_icon_url' => $player->league?->iconUrl,
            'heroes' => array_map(
                static fn (mixed $hero): mixed => is_array($hero) ? array_diff_key($hero, ['equipment' => null]) : $hero,
                $player->raw['heroes'] ?? [],
            ),
            'troops' => $player->raw['troops'] ?? [],
            'spells' => $player->raw['spells'] ?? [],
            'hero_equipment' => $player->raw['heroEquipment'] ?? [],
            'labels' => $player->raw['labels'] ?? [],
            'achievements' => $player->raw['achievements'] ?? [],
            'raw_payload' => $player->raw,
            'api_synced_at' => now(),
        ];
    }
}
