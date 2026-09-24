<?php

namespace App\Domain\Users\Enums;

enum ProfileVisibility: string
{
    case Public = 'public';
    case Members = 'members';
    case Private = 'private';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Everyone',
            self::Members => 'Members only',
            self::Private => 'Only me',
        };
    }
}
