<?php

namespace App\Domain\Moderation\Models;

use App\Domain\Moderation\Enums\SanctionType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Active and historical sanctions (specs/07 `user_sanctions`), so a status check is one indexed read
 * and expiry is a scheduled job. Written through the SanctionService; the only permitted mutation is
 * lifting (lifted_by/lifted_at). `user_id` and `issued_by` are never fillable — set explicitly.
 *
 * @property int $id
 * @property int $user_id
 * @property int $issued_by
 * @property SanctionType $type
 * @property string $reason_code
 * @property string $public_reason
 * @property CarbonInterface $starts_at
 * @property CarbonInterface|null $expires_at
 * @property CarbonInterface|null $lifted_at
 */
class UserSanction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'type', 'reason_code', 'public_reason', 'internal_note',
        'starts_at', 'expires_at', 'moderation_action_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => SanctionType::class,
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'lifted_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
