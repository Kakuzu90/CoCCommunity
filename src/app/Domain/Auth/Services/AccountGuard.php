<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Enums\UserRole;
use App\Domain\Auth\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Read-only status/role predicates over the authenticated principal, so Presentation middleware can
 * gate requests without touching the `User` Eloquent model directly (Deptrac keeps Presentation off
 * a module's internals). Every method is defensive against a null or foreign Authenticatable.
 */
final class AccountGuard
{
    public function roleAtLeast(?Authenticatable $user, UserRole $role): bool
    {
        return $user instanceof User && $user->role->atLeast($role);
    }

    public function hasVerifiedEmail(?Authenticatable $user): bool
    {
        return $user instanceof User && $user->hasVerifiedEmail();
    }

    /** Active accounts may write; every other status is blocked by `EnsureAccountIsActive`. */
    public function canWrite(?Authenticatable $user): bool
    {
        return $user instanceof User && $user->status->canWrite();
    }

    /** Publishing, recruiting and selling additionally require a verified in-game account. */
    public function hasVerifiedCocAccount(?Authenticatable $user): bool
    {
        return $user instanceof User && $user->verified_accounts_count > 0;
    }

    /** The message to surface when a non-active account is refused a write. */
    public function writeBlockedMessage(?Authenticatable $user): string
    {
        return $user instanceof User
            ? $user->status->writeBlockedMessage()
            : 'This account cannot make changes right now.';
    }
}
