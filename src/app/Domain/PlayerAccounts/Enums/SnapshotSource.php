<?php

namespace App\Domain\PlayerAccounts\Enums;

enum SnapshotSource: string
{
    case Scheduled = 'scheduled';
    case Manual = 'manual';
    case Verification = 'verification';
}
