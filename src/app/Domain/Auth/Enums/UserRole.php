<?php

namespace App\Domain\Auth\Enums;

/**
 * Platform role (specs/07 `users.role`, matrix in specs/04). Four fixed, hierarchical roles: each
 * level includes everything below it, so authorization checks compare levels rather than equality.
 * Deliberately an enum, not a permissions package (specs/04 §1).
 */
enum UserRole: string
{
    case User = 'user';
    case Moderator = 'moderator';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    /** Rank in the hierarchy; higher outranks lower. Never persisted — derived from the case. */
    public function level(): int
    {
        return match ($this) {
            self::User => 0,
            self::Moderator => 1,
            self::Admin => 2,
            self::SuperAdmin => 3,
        };
    }

    /** True when this role sits at or above $role in the hierarchy. */
    public function atLeast(self $role): bool
    {
        return $this->level() >= $role->level();
    }

    /** Moderator and above hold staff powers; a plain user does not. */
    public function isStaff(): bool
    {
        return $this !== self::User;
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    /** Human label for the component gallery and admin surfaces. */
    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Moderator => 'Moderator',
            self::Admin => 'Admin',
            self::SuperAdmin => 'Super Admin',
        };
    }
}
