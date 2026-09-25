<?php

namespace App\Domain\PlayerAccounts\Enums;

/** The result of a token verification attempt (specs/13 §3 steps 8, 3.1). An invalid token is normal. */
enum VerificationOutcome: string
{
    case Verified = 'verified';
    case InvalidToken = 'invalid_token';
}
