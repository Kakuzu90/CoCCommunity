<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Actions;

use App\Models\User;
use App\Modules\CocIntegration\Support\Tag;
use App\Modules\PlayerAccounts\Enums\ClaimMethod;
use App\Modules\PlayerAccounts\Enums\ClaimStatus;
use App\Modules\PlayerAccounts\Exceptions\TagAlreadyClaimedException;
use App\Modules\PlayerAccounts\Models\CocAccount;
use App\Modules\PlayerAccounts\Models\CocAccountClaim;

/**
 * Opens a manual ownership dispute for admin review — the fallback when a user
 * cannot pass the in-game token but believes the account is theirs.
 */
class OpenOwnershipDispute
{
    /**
     * @param  array<string, mixed>  $evidence
     */
    public function handle(User $user, string $rawTag, array $evidence = []): CocAccountClaim
    {
        $tag = Tag::normalize($rawTag);

        $account = CocAccount::where('tag', $tag)->first();

        // Nothing to dispute if no one holds the tag — link it instead.
        if ($account === null) {
            throw new TagAlreadyClaimedException($tag);
        }

        return CocAccountClaim::create([
            'coc_account_id' => $account->id,
            'claimant_user_id' => $user->id,
            'method' => ClaimMethod::Dispute,
            'status' => ClaimStatus::Open,
            'evidence' => $evidence,
        ]);
    }
}
