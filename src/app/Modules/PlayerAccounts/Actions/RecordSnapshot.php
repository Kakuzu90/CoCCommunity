<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Actions;

use App\Modules\CocIntegration\DTOs\PlayerData;
use App\Modules\PlayerAccounts\Models\CocAccount;
use App\Modules\PlayerAccounts\Models\CocAccountSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Persists a point-in-time snapshot from the API and refreshes the account's
 * display fields. Snapshots are the app's source of truth for player data.
 */
class RecordSnapshot
{
    public function handle(CocAccount $account, PlayerData $player): CocAccountSnapshot
    {
        return DB::transaction(function () use ($account, $player): CocAccountSnapshot {
            $snapshot = $account->snapshots()->create([
                'th_level' => $player->townHall,
                'trophies' => $player->trophies,
                'war_stars' => $player->warStars,
                'league' => $player->league,
                'data' => $player->raw,
                'fetched_at' => now(),
            ]);

            $account->forceFill([
                'ign' => $player->name,
                'last_synced_at' => now(),
            ])->save();

            return $snapshot;
        });
    }
}
