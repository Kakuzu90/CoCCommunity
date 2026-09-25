<?php

namespace App\Domain\PlayerAccounts\Models;

use App\Domain\PlayerAccounts\Enums\SnapshotSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $coc_account_id
 * @property Carbon $captured_at
 */
final class CocAccountSnapshot extends Model
{
    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'coc_account_id', 'captured_at', 'th_level', 'xp_level', 'trophies',
        'best_trophies', 'war_stars', 'attack_wins', 'defense_wins', 'donations',
        'clan_tag', 'league_id', 'heroes', 'troops', 'spells', 'hero_equipment', 'source',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'heroes' => 'array',
            'troops' => 'array',
            'spells' => 'array',
            'hero_equipment' => 'array',
            'source' => SnapshotSource::class,
        ];
    }
}
