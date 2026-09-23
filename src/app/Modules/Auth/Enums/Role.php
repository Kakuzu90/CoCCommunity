<?php

declare(strict_types=1);

namespace App\Modules\Auth\Enums;

enum Role: string
{
    case User = 'User';
    case Moderator = 'Moderator';
    case Admin = 'Admin';
    case SuperAdmin = 'Super Admin';

    public function permissions(): array
    {
        return match ($this) {
            self::User => [],
            self::Moderator => ['admin.access', 'audit.view-own'],
            self::Admin => ['admin.access', 'audit.view-own', 'audit.view-all', 'roles.manage'],
            self::SuperAdmin => ['admin.access', 'audit.view-own', 'audit.view-all', 'roles.manage', 'settings.manage'],
        };
    }
}
