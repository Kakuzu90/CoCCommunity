<?php

namespace App\Domain\Audit\Enums;

/**
 * The privileged actions recorded to `audit_logs` (specs/07, specs/04 §2 "View audit log"). The
 * column is a free varchar(60) so later modules add their own action strings without a migration;
 * this enum names the ones Admin v1 emits and gives the viewer a stable human label. Values are
 * dotted `subject.verb` so the log reads well and filters group naturally.
 */
enum AuditAction: string
{
    case UserWarned = 'user.warned';
    case UserRestricted = 'user.restricted';
    case UserSuspended = 'user.suspended';
    case UserBanned = 'user.banned';
    case SanctionLifted = 'user.sanction_lifted';
    case CocAccountVerified = 'coc_account.verified';
    case CocAccountSuperseded = 'coc_account.superseded';
    case CocAccountDetached = 'coc_account.detached';
    case CocDisputeOpened = 'coc_dispute.opened';
    case CocDisputeTransferred = 'coc_dispute.transferred';
    case CocDisputeDenied = 'coc_dispute.denied';
    case CocDisputeTagSuspended = 'coc_dispute.tag_suspended';

    public function label(): string
    {
        return match ($this) {
            self::UserWarned => 'User warned',
            self::UserRestricted => 'User restricted',
            self::UserSuspended => 'User suspended',
            self::UserBanned => 'User banned',
            self::SanctionLifted => 'Sanction lifted',
            self::CocAccountVerified => 'CoC account verified',
            self::CocAccountSuperseded => 'CoC account ownership superseded',
            self::CocAccountDetached => 'CoC account detached',
            self::CocDisputeOpened => 'CoC ownership dispute opened',
            self::CocDisputeTransferred => 'CoC ownership transferred by admin',
            self::CocDisputeDenied => 'CoC ownership dispute denied',
            self::CocDisputeTagSuspended => 'CoC tag suspended after dispute',
        };
    }

    /** Label for any stored action string, falling back to a humanised form for actions not cased here. */
    public static function labelFor(string $action): string
    {
        return self::tryFrom($action)?->label()
            ?? ucfirst(str_replace(['.', '_'], ' ', $action));
    }
}
