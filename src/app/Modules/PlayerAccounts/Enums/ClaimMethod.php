<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Enums;

enum ClaimMethod: string
{
    // Ownership proven by passing the in-game API token.
    case Token = 'token';
    // Manual dispute for admin review where token proof is not possible.
    case Dispute = 'dispute';
}
