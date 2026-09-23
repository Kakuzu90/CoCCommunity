<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Models;

use App\Models\User;
use App\Modules\PlayerAccounts\Enums\ClaimMethod;
use App\Modules\PlayerAccounts\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CocAccountClaim extends Model
{
    protected $fillable = [
        'coc_account_id', 'claimant_user_id', 'method', 'status', 'evidence', 'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'method' => ClaimMethod::class,
            'status' => ClaimStatus::class,
            'evidence' => 'array',
        ];
    }

    /** @return BelongsTo<CocAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(CocAccount::class, 'coc_account_id');
    }

    /** @return BelongsTo<User, $this> */
    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimant_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
