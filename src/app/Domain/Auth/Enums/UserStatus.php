<?php

namespace App\Domain\Auth\Enums;

/**
 * Account status (specs/07 `users.status`), orthogonal to role. Gates authentication and, through
 * the write-gating middleware, write access (specs/04 §1, §3).
 */
enum UserStatus: string
{
    case Active = 'active';
    case Restricted = 'restricted';
    case Suspended = 'suspended';
    case Banned = 'banned';
    case PendingDeletion = 'pending_deletion';

    /**
     * Restricted users can sign in with limited writes. Pending-deletion users can sign in only to
     * cancel during the grace window; other writes remain blocked.
     */
    public function canAuthenticate(): bool
    {
        return match ($this) {
            self::Active, self::Restricted, self::PendingDeletion => true,
            self::Suspended, self::Banned => false,
        };
    }

    /**
     * Whether the account may perform writes (publish, comment, message, sanctioned actions). Only
     * `active` qualifies: `EnsureAccountIsActive` blocks every other status (specs/04 §3).
     */
    public function canWrite(): bool
    {
        return $this === self::Active;
    }

    /** A user-facing reason shown when authentication is refused. */
    public function lockedMessage(): string
    {
        return match ($this) {
            self::Suspended => 'This account is suspended.',
            self::Banned => 'This account has been banned.',
            self::PendingDeletion => 'This account is scheduled for deletion.',
            default => 'This account cannot sign in right now.',
        };
    }

    /** A user-facing reason shown when a write is refused for a non-active account. */
    public function writeBlockedMessage(): string
    {
        return match ($this) {
            self::Restricted => 'Your account is restricted; posting, commenting and messaging are paused.',
            self::Suspended => 'This account is suspended.',
            self::Banned => 'This account has been banned.',
            self::PendingDeletion => 'This account is scheduled for deletion; cancel deletion to continue.',
            default => 'This account cannot make changes right now.',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Restricted => 'Restricted',
            self::Suspended => 'Suspended',
            self::Banned => 'Banned',
            self::PendingDeletion => 'Pending deletion',
        };
    }
}
