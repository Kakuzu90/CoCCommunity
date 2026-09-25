<?php

namespace App\Domain\PlayerAccounts\Queries;

use App\Domain\PlayerAccounts\Data\CocAccountSummary;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Models\SyncState;

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
        $accounts = CocAccount::query()
            ->where('user_id', $userId)
            ->where('status', '!=', CocAccountStatus::Released->value)
            ->orderByDesc('is_featured')
            ->orderByDesc('verified_at')
            ->orderByDesc('id')
            ->get();

        $stale = SyncState::query()
            ->where('resource_type', 'coc_account')
            ->whereIn('resource_id', $accounts->pluck('id'))
            ->get(['resource_id', 'stale', 'consecutive_failures'])
            ->keyBy('resource_id');

        return array_values($accounts
            ->map(function (CocAccount $account) use ($stale): CocAccountSummary {
                $state = $stale->get($account->id);

                return CocAccountSummary::fromModel($account, $state !== null && ($state->stale || $state->consecutive_failures > 0));
            })
            ->all());
    }
}
