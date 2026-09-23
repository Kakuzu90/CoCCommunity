<?php

declare(strict_types=1);

namespace App\Modules\Auth\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Banned = 'banned';
}
