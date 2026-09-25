<?php

namespace App\Domain\PlayerAccounts\Services;

use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Models\CocAccount;

final class VerifiedAccountLookup
{
    public function ownedBy(int $userId, int $accountId): bool
    {
        return CocAccount::query()->whereKey($accountId)->where('user_id', $userId)
            ->where('status', CocAccountStatus::Verified->value)->exists();
    }

    /** @return list<array{id: int, name: string, th_level: int}> */
    public function optionsFor(int $userId): array
    {
        return array_values(CocAccount::query()->where('user_id', $userId)
            ->where('status', CocAccountStatus::Verified->value)
            ->orderByDesc('is_featured')->get(['id', 'ign', 'th_level'])
            ->map(fn (CocAccount $account): array => [
                'id' => $account->id,
                'name' => $account->ign,
                'th_level' => $account->th_level,
            ])->all());
    }
}
