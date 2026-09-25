<?php

namespace App\Domain\PlayerAccounts\Enums;

/** How ownership was established (specs/13 §3.1, §5). The in-game token is the only automatic path. */
enum VerificationMethod: string
{
    case ApiToken = 'api_token';
    case Admin = 'admin';
}
