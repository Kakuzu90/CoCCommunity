<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Actions;

use App\Models\User;
use App\Modules\CocIntegration\Support\Tag;
use App\Modules\PlayerAccounts\Enums\AccountState;
use App\Modules\PlayerAccounts\Exceptions\TagAlreadyClaimedException;
use App\Modules\PlayerAccounts\Models\CocAccount;

/**
 * Adds an unverified account for a user. Ownership is not proven here — that
 * requires the in-game token (see RequestOwnershipVerification). A tag held by
 * anyone else is refused; the user must verify with a token or open a dispute.
 */
class LinkAccount
{
    public function handle(User $user, string $rawTag): CocAccount
    {
        $tag = Tag::normalize($rawTag);

        $existing = CocAccount::withTrashed()->where('tag', $tag)->first();

        if ($existing !== null) {
            if ($existing->user_id !== $user->id) {
                throw new TagAlreadyClaimedException($tag);
            }

            if ($existing->trashed()) {
                $existing->restore();
            }

            return $existing;
        }

        return CocAccount::create([
            'user_id' => $user->id,
            'tag' => $tag,
            'state' => AccountState::Unverified,
        ]);
    }
}
