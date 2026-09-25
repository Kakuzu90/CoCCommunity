<?php

namespace App\Domain\PlayerAccounts\Enums;

/** Which path an attach attempt took (specs/07 coc_account_claims). Dispute/admin arrive in later tasks. */
enum ClaimMethod: string
{
    case ApiToken = 'api_token';
    case Dispute = 'dispute';
    case Admin = 'admin';
}
