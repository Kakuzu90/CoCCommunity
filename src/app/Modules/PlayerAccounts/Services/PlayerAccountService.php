<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Services;

use App\Models\User;
use App\Modules\PlayerAccounts\Enums\AccountState;
use App\Modules\PlayerAccounts\Models\CocAccount;
use App\Modules\PlayerAccounts\Models\CocAccountSnapshot;
use Illuminate\Support\Collection;

/**
 * Read model other modules use for player accounts, so they never touch this
 * module's Eloquent models directly.
 */
class PlayerAccountService
{
    /** @return Collection<int, CocAccount> */
    public function linkedAccountsFor(User $user): Collection
    {
        return CocAccount::with('latestSnapshot')
            ->where('user_id', $user->id)
            ->orderByDesc('verified_at')
            ->orderByDesc('id')
            ->get();
    }

    /** @return Collection<int, CocAccount> */
    public function verifiedAccountsFor(User $user): Collection
    {
        return CocAccount::with('latestSnapshot')
            ->where('user_id', $user->id)
            ->where('state', AccountState::Verified->value)
            ->orderByDesc('verified_at')
            ->get();
    }

    public function latestSnapshot(CocAccount $account): ?CocAccountSnapshot
    {
        return $account->latestSnapshot()->first();
    }
}
