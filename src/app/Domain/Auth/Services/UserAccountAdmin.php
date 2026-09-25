<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\AccountStatusChange;
use App\Domain\Auth\Enums\UserStatus;
use App\Domain\Auth\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Gate;

/**
 * Authorized account-status transitions (specs/04 §2, specs/12 §6). This is where the `User` model and
 * `UserPolicy` live, so every sanction re-checks authorization on the action — structural rule 1
 * (actor must strictly outrank the target, never self) is enforced here via the policy, not merely by
 * a role gate at the route. Callers pass ids and receive a DTO; the model never crosses the boundary.
 *
 * Status columns (`status`, `status_reason`, `status_expires_at`) are assigned directly rather than
 * mass-assigned: they are deliberately absent from the User model's `$fillable` (specs CLAUDE.md).
 */
final class UserAccountAdmin
{
    public function warn(int $actorId, int $targetId): AccountStatusChange
    {
        // A warning is a record only; the account status is unchanged (specs/12 §6).
        return $this->transition($actorId, $targetId, 'warn', null, null, null);
    }

    public function restrict(int $actorId, int $targetId, string $reason, ?CarbonInterface $expiresAt): AccountStatusChange
    {
        return $this->transition($actorId, $targetId, 'restrict', UserStatus::Restricted, $reason, $expiresAt);
    }

    public function suspend(int $actorId, int $targetId, string $reason, ?CarbonInterface $expiresAt): AccountStatusChange
    {
        return $this->transition($actorId, $targetId, 'suspend', UserStatus::Suspended, $reason, $expiresAt);
    }

    public function ban(int $actorId, int $targetId, string $reason): AccountStatusChange
    {
        // A ban is permanent — no expiry (specs/04 §1). Tag release after 30 days is a later job.
        return $this->transition($actorId, $targetId, 'ban', UserStatus::Banned, $reason, null);
    }

    public function lift(int $actorId, int $targetId): AccountStatusChange
    {
        return $this->transition($actorId, $targetId, 'liftSanction', UserStatus::Active, null, null);
    }

    private function transition(
        int $actorId,
        int $targetId,
        string $policyMethod,
        ?UserStatus $newStatus,
        ?string $reason,
        ?CarbonInterface $expiresAt,
    ): AccountStatusChange {
        $actor = User::query()->findOrFail($actorId);
        $target = User::query()->findOrFail($targetId);

        // Re-check on the action. Throws AuthorizationException (403) on a peer/superior/self target
        // or an under-ranked actor. Gate::before still grants super admin ahead of the policy.
        Gate::forUser($actor)->authorize($policyMethod, $target);

        $before = $target->status;

        if ($newStatus !== null) {
            $target->status = $newStatus;
            $target->status_reason = $reason;
            $target->status_expires_at = $expiresAt;
            $target->save();
        }

        return new AccountStatusChange(
            targetId: $target->id,
            targetUsername: $target->username,
            actorId: $actor->id,
            actorRole: $actor->role->value,
            before: $before,
            after: $target->status,
        );
    }
}
