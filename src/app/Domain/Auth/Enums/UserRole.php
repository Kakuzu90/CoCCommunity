<?php

namespace App\Domain\Auth\Enums;

/** Platform role (specs/07 `users.role`). The gate/hierarchy scaffold is a later Phase 1 task. */
enum UserRole: string
{
    case User = 'user';
    case Moderator = 'moderator';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function isStaff(): bool
    {
        return $this !== self::User;
    }
}
