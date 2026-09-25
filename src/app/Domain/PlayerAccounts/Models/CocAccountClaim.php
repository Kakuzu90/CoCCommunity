<?php

namespace App\Domain\PlayerAccounts\Models;

use App\Domain\PlayerAccounts\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * The immutable forensic record of every attach/verify attempt (specs/07, 13 §3). Written by the
 * account services; `user_id` and `coc_account_id` are set directly, not mass-assigned. No updated_at.
 *
 * @property ClaimStatus $status
 * @property ?int $coc_account_id
 * @property ?int $user_id
 */
final class CocAccountClaim extends Model
{
    public const UPDATED_AT = null;

    // `status`, `user_id` and `coc_account_id` are set directly by the service, never mass-assigned.
    protected $fillable = [
        'tag_normalized', 'method', 'failure_reason', 'ip_hash', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'status' => ClaimStatus::class,
        ];
    }
}
