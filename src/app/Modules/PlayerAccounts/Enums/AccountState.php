<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Enums;

enum AccountState: string
{
    case Unverified = 'unverified';
    case Verified = 'verified';
    case Disputed = 'disputed';
    case Suspended = 'suspended';
    case NeedsReverify = 'needs_reverify';
}
