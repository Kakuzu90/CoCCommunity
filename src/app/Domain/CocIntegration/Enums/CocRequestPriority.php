<?php

namespace App\Domain\CocIntegration\Enums;

/**
 * Which rate-limit bucket a call draws from (specs/09 §4). User-triggered lookups are interactive and
 * hold a reserved share of the budget; background sync yields to them under pressure.
 */
enum CocRequestPriority: string
{
    case Interactive = 'interactive';
    case Background = 'background';
    case Manual = 'manual';

    public function limiterKey(): string
    {
        return $this === self::Manual ? 'coc-interactive' : 'coc-'.$this->value;
    }
}
