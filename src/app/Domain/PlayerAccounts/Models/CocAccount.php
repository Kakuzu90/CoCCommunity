<?php

namespace App\Domain\PlayerAccounts\Models;

use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Enums\VerificationMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A CoC tag attached to a user (specs/07, 13). `user_id`, `status`, `verified_at`,
 * `verification_method`, `is_featured` and counters are never mass-assignable — ownership transitions
 * go through the account services, which set them directly. Only API-synced progression data is fillable.
 *
 * @property int $id
 * @property string $tag
 * @property string $tag_normalized
 * @property CocAccountStatus $status
 * @property ?int $user_id
 * @property ?int $previous_user_id
 * @property bool $is_featured
 * @property string $ign
 * @property int $th_level
 * @property ?int $builder_hall_level
 * @property int $xp_level
 * @property int $trophies
 * @property int $best_trophies
 * @property int $builder_trophies
 * @property int $war_stars
 * @property int $attack_wins
 * @property int $defense_wins
 * @property int $donations
 * @property int $donations_received
 * @property ?string $clan_tag
 * @property ?string $clan_role
 * @property ?int $league_id
 * @property ?string $league_name
 * @property ?string $league_icon_url
 * @property array<int, mixed> $troops
 * @property array<int, mixed> $heroes
 * @property array<int, mixed> $spells
 * @property array<int, mixed> $hero_equipment
 * @property array<int, mixed> $labels
 * @property array<int, mixed> $achievements
 * @property array<string, mixed>|null $raw_payload
 * @property ?Carbon $api_synced_at
 * @property ?Carbon $verified_at
 */
final class CocAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ign', 'th_level', 'builder_hall_level', 'xp_level', 'trophies', 'best_trophies',
        'builder_trophies', 'war_stars', 'attack_wins', 'defense_wins', 'donations',
        'donations_received', 'clan_tag', 'clan_id', 'clan_role', 'league_id', 'league_name',
        'league_icon_url', 'troops', 'heroes', 'spells', 'hero_equipment', 'labels',
        'achievements', 'raw_payload', 'api_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CocAccountStatus::class,
            'verification_method' => VerificationMethod::class,
            'verified_at' => 'datetime',
            'api_synced_at' => 'datetime',
            'is_featured' => 'boolean',
            'troops' => 'array',
            'heroes' => 'array',
            'spells' => 'array',
            'hero_equipment' => 'array',
            'labels' => 'array',
            'achievements' => 'array',
            'raw_payload' => 'array',
        ];
    }
}
