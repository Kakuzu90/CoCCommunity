<?php

namespace App\Domain\Moderation\Models;

use App\Domain\Moderation\Services\SanctionService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Immutable record of what a moderator did (specs/07 `moderation_actions`). No updates, no deletes,
 * no `updated_at`. Written only through {@see SanctionService}.
 *
 * @property int $id
 * @property int $actor_id
 * @property string $action
 * @property string $reason_code
 * @property CarbonInterface $created_at
 */
class ModerationAction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'case_id', 'actor_id', 'action', 'target_type', 'target_id', 'target_user_id',
        'reason_code', 'note', 'duration_hours', 'metadata', 'ip_hash',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
