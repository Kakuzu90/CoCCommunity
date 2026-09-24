<?php

namespace App\Domain\Auth\Enums;

/** Account status (specs/07 `users.status`). Gates login and, later, write authorization. */
enum UserStatus: string
{
    case Active = 'active';
    case Restricted = 'restricted';
    case Suspended = 'suspended';
    case Banned = 'banned';
    case PendingDeletion = 'pending_deletion';

    /**
     * Whether a session may be established. Restricted users can still sign in (their writes are
     * curtailed by policy); suspended, banned and pending-deletion accounts cannot (specs/04 §4).
     */
    public function canAuthenticate(): bool
    {
        return match ($this) {
            self::Active, self::Restricted => true,
            self::Suspended, self::Banned, self::PendingDeletion => false,
        };
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
}
