<?php

namespace App\Domain\PlayerAccounts\Enums;

/**
 * Lifecycle of an attached CoC account (specs/13 §2). Only `verified` grants the badge, publishing
 * rights and background sync; `unverified` is advisory only. A tag has at most one `verified` owner
 * platform-wide (enforced by a partial unique index).
 */
enum CocAccountStatus: string
{
    case Unverified = 'unverified';
    case Verified = 'verified';
    case Disputed = 'disputed';
    case Suspended = 'suspended';
    case Released = 'released';

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Unverified',
            self::Verified => 'Verified',
            self::Disputed => 'Under review',
            self::Suspended => 'Suspended',
            self::Released => 'Released',
        };
    }

    public function isVerified(): bool
    {
        return $this === self::Verified;
    }

    /** Whether another user may still claim this tag (verification always wins). */
    public function isClaimableByOthers(): bool
    {
        return $this === self::Unverified || $this === self::Released;
    }
}
