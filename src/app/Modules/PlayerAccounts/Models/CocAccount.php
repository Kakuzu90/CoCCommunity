<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Models;

use App\Models\User;
use App\Modules\PlayerAccounts\Enums\AccountState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CocAccount extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'tag', 'ign', 'state', 'verified_at', 'last_synced_at'];

    protected function casts(): array
    {
        return [
            'state' => AccountState::class,
            'verified_at' => 'immutable_datetime',
            'last_synced_at' => 'immutable_datetime',
        ];
    }

    public function isVerified(): bool
    {
        return $this->state === AccountState::Verified;
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<CocAccountSnapshot, $this> */
    public function snapshots(): HasMany
    {
        return $this->hasMany(CocAccountSnapshot::class);
    }

    /** @return HasOne<CocAccountSnapshot, $this> */
    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(CocAccountSnapshot::class)->latestOfMany('fetched_at');
    }

    /** @return HasMany<CocAccountClaim, $this> */
    public function claims(): HasMany
    {
        return $this->hasMany(CocAccountClaim::class);
    }
}
