<?php

namespace App\Domain\Auth\Enums;

/**
 * The role-gated abilities of the permission matrix (specs/04 §2, §3), as Gate ability names. Each
 * case carries the minimum role that may exercise it; ownership-scoped abilities (edit own profile,
 * publish own base) are uniform across roles and live in their per-model policies, not here.
 *
 * This enum is the single source the matrix test asserts against, so the matrix cannot drift from
 * the gate definitions in `AuthServiceProvider`.
 */
enum Ability: string
{
    // Back-office entry points.
    case AccessAdmin = 'access-admin';
    case AccessModeration = 'access-moderation';

    // Moderation (moderator and above).
    case ViewReportQueue = 'view-report-queue';
    case ClaimReport = 'claim-report';
    case HideContent = 'hide-content';
    case WarnUser = 'warn-user';
    case RestrictUser = 'restrict-user';
    case ReviewMediaQuarantine = 'review-media-quarantine';
    case ViewModerationLog = 'view-moderation-log';

    // Administration (admin and above).
    case RemoveContent = 'remove-content';
    case SuspendUser = 'suspend-user';
    case BanUser = 'ban-user';
    case LiftSanction = 'lift-sanction';
    case ResolveDisputes = 'resolve-disputes';
    case ForceOwnershipTransfer = 'force-ownership-transfer';
    case ApproveMarketplaceSeller = 'approve-marketplace-seller';
    case ManageTags = 'manage-tags';
    case ViewAuditLog = 'view-audit-log';

    // Platform owners only (super admin).
    case ManageRoles = 'manage-roles';
    case ManageSettings = 'manage-settings';
    case HardDeleteUser = 'hard-delete-user';

    // Denied to everyone, super admin included (specs/04 §2, locked decision: no impersonation).
    case Impersonate = 'impersonate';

    /**
     * The lowest role that holds this ability, or null when no role may ever hold it (impersonate).
     * `Gate::before` still grants super admin every non-null ability.
     */
    public function minimumRole(): ?UserRole
    {
        return match ($this) {
            self::AccessModeration,
            self::ViewReportQueue,
            self::ClaimReport,
            self::HideContent,
            self::WarnUser,
            self::RestrictUser,
            self::ReviewMediaQuarantine,
            self::ViewModerationLog => UserRole::Moderator,

            self::AccessAdmin,
            self::RemoveContent,
            self::SuspendUser,
            self::BanUser,
            self::LiftSanction,
            self::ResolveDisputes,
            self::ForceOwnershipTransfer,
            self::ApproveMarketplaceSeller,
            self::ManageTags,
            self::ViewAuditLog => UserRole::Admin,

            self::ManageRoles,
            self::ManageSettings,
            self::HardDeleteUser => UserRole::SuperAdmin,

            self::Impersonate => null,
        };
    }

    /** Whether $role may exercise this ability, by the matrix alone (status is gated elsewhere). */
    public function grantedTo(UserRole $role): bool
    {
        $minimum = $this->minimumRole();

        return $minimum !== null && $role->atLeast($minimum);
    }

    /** @return list<self> */
    public static function all(): array
    {
        return self::cases();
    }
}
