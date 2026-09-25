<?php

namespace App\Domain\PlayerAccounts\Models;

use App\Domain\PlayerAccounts\Enums\SyncTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $resource_id
 * @property int $consecutive_failures
 * @property int $not_found_failures
 * @property SyncTier $tier
 * @property ?Carbon $viewed_at
 * @property ?Carbon $next_due_at
 * @property bool $stale
 * @property bool $flagged
 */
final class SyncState extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'resource_type', 'resource_id', 'last_attempt_at', 'last_success_at',
        'viewed_at', 'consecutive_failures', 'not_found_failures', 'next_due_at',
        'tier', 'stale', 'flagged',
    ];

    protected function casts(): array
    {
        return [
            'last_attempt_at' => 'datetime',
            'last_success_at' => 'datetime',
            'viewed_at' => 'datetime',
            'next_due_at' => 'datetime',
            'tier' => SyncTier::class,
            'stale' => 'boolean',
            'flagged' => 'boolean',
        ];
    }
}
