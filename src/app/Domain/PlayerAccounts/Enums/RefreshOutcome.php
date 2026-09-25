<?php

namespace App\Domain\PlayerAccounts\Enums;

enum RefreshOutcome: string
{
    case Refreshed = 'refreshed';
    case Queued = 'queued';
}
