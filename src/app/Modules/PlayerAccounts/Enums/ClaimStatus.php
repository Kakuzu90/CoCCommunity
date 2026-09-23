<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Enums;

enum ClaimStatus: string
{
    case Open = 'open';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
