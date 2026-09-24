<?php

namespace App\Domain\Auth\Policies;

use App\Domain\Auth\Enums\UserRole;
use App\Domain\Auth\Models\User;

/**
 * Authorization for actions taken *on* a user account (specs/04 §2 sanction rows). Ability-to-role
 * thresholds mirror the matrix; on top of that, structural rule 1 applies: an actor may only act on
 * a target strictly below them in the hierarchy — escalation only, never on a peer or superior
 * (specs/04 §2). `Gate::before` grants super admin ahead of this policy, so these methods decide the
 * moderator and admin cases.
 */
final class UserPolicy
{
    /** Staff may browse the user list; a plain user may not. */
    public function viewAny(User $actor): bool
    {
        return $actor->role->isStaff();
    }

    public function warn(User $actor, User $target): bool
    {
        return $actor->role->atLeast(UserRole::Moderator) && $this->outranks($actor, $target);
    }

    public function restrict(User $actor, User $target): bool
    {
        return $actor->role->atLeast(UserRole::Moderator) && $this->outranks($actor, $target);
    }

    public function suspend(User $actor, User $target): bool
    {
        return $actor->role->atLeast(UserRole::Admin) && $this->outranks($actor, $target);
    }

    public function ban(User $actor, User $target): bool
    {
        return $actor->role->atLeast(UserRole::Admin) && $this->outranks($actor, $target);
    }

    public function liftSanction(User $actor, User $target): bool
    {
        return $actor->role->atLeast(UserRole::Admin) && $this->outranks($actor, $target);
    }

    /** Role changes are super-admin only (also the `manage-roles` gate) and audited by the service. */
    public function changeRole(User $actor, User $target): bool
    {
        return $actor->role->isSuperAdmin() && $actor->isNot($target);
    }

    public function hardDelete(User $actor, User $target): bool
    {
        return $actor->role->isSuperAdmin() && $actor->isNot($target);
    }

    /**
     * Structural rule 1: the actor must sit strictly above the target, and never act on themselves.
     * This is what stops a moderator touching another moderator, or anyone sanctioning their peers.
     */
    private function outranks(User $actor, User $target): bool
    {
        return $actor->isNot($target) && $actor->role->level() > $target->role->level();
    }
}
