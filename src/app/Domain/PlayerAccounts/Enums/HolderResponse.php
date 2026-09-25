<?php

namespace App\Domain\PlayerAccounts\Enums;

/**
 * How the current holder answers a dispute filed against them (specs/13 §5 step 3). Re-verifying with a
 * token is not here — that runs through the ordinary verification path and ends the dispute instantly.
 */
enum HolderResponse: string
{
    case Counter = 'counter';
    case Release = 'release';
}
