<?php

namespace App\Domain\Users\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Denormalised per-user counters (specs/07 `user_stats`) so a profile page is a single row read.
 * Internal to the Users module. Counters are maintained by the features that own them, never
 * mass-assigned here.
 *
 * @property int $user_id
 * @property int $bases_published
 * @property int $total_base_likes
 * @property int $total_base_copies
 * @property int $total_base_views
 * @property int $comments_posted
 * @property CarbonInterface|null $recomputed_at
 */
class UserStats extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'int';

    // Counters are maintained by the features that own them; `user_id` is set explicitly, never
    // mass-assigned (specs/11 mass-assignment; the convention test forbids it in $fillable).
    /** @var list<string> */
    protected $fillable = [
        'bases_published',
        'total_base_likes',
        'total_base_copies',
        'total_base_views',
        'comments_posted',
        'recomputed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'recomputed_at' => 'datetime',
        ];
    }
}
