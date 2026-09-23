<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CocAccountSnapshot extends Model
{
    protected $fillable = [
        'coc_account_id', 'th_level', 'trophies', 'war_stars', 'league', 'data', 'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'th_level' => 'integer',
            'trophies' => 'integer',
            'war_stars' => 'integer',
            'data' => 'array',
            'fetched_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<CocAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(CocAccount::class, 'coc_account_id');
    }
}
