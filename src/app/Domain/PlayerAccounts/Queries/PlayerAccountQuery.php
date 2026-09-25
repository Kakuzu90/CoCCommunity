<?php

namespace App\Domain\PlayerAccounts\Queries;

use App\Domain\PlayerAccounts\Data\CocAccountSummary;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Models\CocAccount;

/**
 * Read side of a user's attached accounts (specs/13, 18 §6). Returns view DTOs so Presentation never
 * holds a model. Released rows are hidden — they are history, not the user's current accounts.
 */
final class PlayerAccountQuery
{
    /**
     * @return list<CocAccountSummary>
     */
    public function forUser(int $userId): array
    {
        return CocAccount::query()
            ->where('user_id', $userId)
            ->where('status', '!=', CocAccountStatus::Released->value)
            ->orderByDesc('is_featured')
            ->orderByDesc('verified_at')
            ->orderByDesc('id')
            ->get()
            ->map(CocAccountSummary::fromModel(...))
            ->all();
    }
}
