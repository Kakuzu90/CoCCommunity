<?php

namespace App\Domain\PlayerAccounts\Enums;

/** Why an attach/verify attempt failed (specs/07 coc_account_claims.failure_reason). */
enum ClaimFailureReason: string
{
    case InvalidToken = 'invalid_token';
    case AlreadyClaimed = 'already_claimed';
    case ApiError = 'api_error';
    case RateLimited = 'rate_limited';
}
