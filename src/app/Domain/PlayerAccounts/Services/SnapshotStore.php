<?php

namespace App\Domain\PlayerAccounts\Services;

use App\Domain\PlayerAccounts\Enums\SnapshotSource;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Models\CocAccountSnapshot;

final class SnapshotStore
{
    public function capture(CocAccount $account, SnapshotSource $source): bool
    {
        $values = [
            'th_level' => $account->th_level,
            'xp_level' => $account->xp_level,
            'trophies' => $account->trophies,
            'best_trophies' => $account->best_trophies,
            'war_stars' => $account->war_stars,
            'attack_wins' => $account->attack_wins,
            'defense_wins' => $account->defense_wins,
            'donations' => $account->donations,
            'clan_tag' => $account->clan_tag,
            'league_id' => $account->league_id,
            'heroes' => $account->heroes ?? [],
            'troops' => $account->troops ?? [],
            'spells' => $account->spells ?? [],
            'hero_equipment' => $account->hero_equipment ?? [],
        ];

        $last = CocAccountSnapshot::query()
            ->where('coc_account_id', $account->id)
            ->orderByDesc('captured_at')
            ->orderByDesc('id')
            ->first();

        if ($last !== null && $this->trackedValues($last, array_keys($values)) === $values) {
            return false;
        }

        CocAccountSnapshot::query()->create([
            'coc_account_id' => $account->id,
            'captured_at' => now(),
            'source' => $source->value,
            ...$values,
        ]);

        return true;
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function trackedValues(CocAccountSnapshot $snapshot, array $keys): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $snapshot->getAttribute($key);
        }

        return $values;
    }
}
