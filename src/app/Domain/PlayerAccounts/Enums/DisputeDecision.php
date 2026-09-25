<?php

namespace App\Domain\PlayerAccounts\Enums;

/**
 * An admin's terminal decision on a dispute (specs/13 §5 step 5). `transfer` moves the tag to the
 * claimant, `deny` returns it to the holder, `suspend` takes it away from both when both look
 * fraudulent. Requesting more information is a status move, not a decision, so it is not here.
 */
enum DisputeDecision: string
{
    case Transfer = 'transfer';
    case Deny = 'deny';
    case Suspend = 'suspend';

    public function label(): string
    {
        return match ($this) {
            self::Transfer => 'Transfer to claimant',
            self::Deny => 'Deny (holder keeps the tag)',
            self::Suspend => 'Suspend the tag',
        };
    }
}
