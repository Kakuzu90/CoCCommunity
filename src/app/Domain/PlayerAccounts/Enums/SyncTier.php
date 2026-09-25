<?php

namespace App\Domain\PlayerAccounts\Enums;

enum SyncTier: string
{
    case Hot = 'hot';
    case Warm = 'warm';
    case Cold = 'cold';
    case Frozen = 'frozen';

    public function intervalSeconds(): int
    {
        return (int) config('coc.sync.tiers.'.$this->value);
    }
}
