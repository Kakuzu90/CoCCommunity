<?php

declare(strict_types=1);

namespace App\Modules\Users\Policies;

use App\Models\User;
use App\Modules\Auth\Enums\Role;

class UserPolicy
{
    public function update(User $actor, User $user): bool
    {
        return ! $actor->trashed() && $actor->is($user);
    }

    public function delete(User $actor, User $user): bool
    {
        return $this->update($actor, $user);
    }

    public function manageRole(User $actor, User $user, Role $role): bool
    {
        if (! $actor->hasVerifiedEmail() || $actor->trashed() || $user->trashed() || ! $actor->can('roles.manage')) {
            return false;
        }

        if ($actor->hasRole(Role::SuperAdmin)) {
            return true;
        }

        return $actor->hasRole(Role::Admin)
            && ! $user->hasAnyRole([Role::Admin, Role::SuperAdmin])
            && in_array($role, [Role::User, Role::Moderator], true);
    }
}
