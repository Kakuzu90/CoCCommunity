<?php

namespace App\Domain\Moderation\Enums;

use App\Domain\Auth\Enums\UserStatus;

/**
 * The four sanctions an moderator/admin can apply (specs/12 §6, specs/04 §2). Each type knows the
 * account status it results in (a warning changes nothing but the record), whether it is time-boxed,
 * and the permission ability that gates it — so the mapping lives in one place the service and the UI
 * both read, never duplicated as string literals.
 */
enum SanctionType: string
{
    case Warning = 'warning';
    case Restriction = 'restriction';
    case Suspension = 'suspension';
    case Ban = 'ban';

    public function label(): string
    {
        return match ($this) {
            self::Warning => 'Warning',
            self::Restriction => 'Restriction',
            self::Suspension => 'Suspension',
            self::Ban => 'Ban',
        };
    }

    /** The account status this sanction moves the user to, or null when status is unchanged (warning). */
    public function resultingStatus(): ?UserStatus
    {
        return match ($this) {
            self::Warning => null,
            self::Restriction => UserStatus::Restricted,
            self::Suspension => UserStatus::Suspended,
            self::Ban => UserStatus::Banned,
        };
    }

    /** The permission ability (specs/04 §2 gate) required to apply this sanction. */
    public function ability(): string
    {
        return match ($this) {
            self::Warning => 'warn-user',
            self::Restriction => 'restrict-user',
            self::Suspension => 'suspend-user',
            self::Ban => 'ban-user',
        };
    }

    /** The UserPolicy method that enforces structural rule 1 (actor must outrank target). */
    public function policyMethod(): string
    {
        return match ($this) {
            self::Warning => 'warn',
            self::Restriction => 'restrict',
            self::Suspension => 'suspend',
            self::Ban => 'ban',
        };
    }

    public function moderationAction(): ModerationActionType
    {
        return match ($this) {
            self::Warning => ModerationActionType::Warn,
            self::Restriction => ModerationActionType::Restrict,
            self::Suspension => ModerationActionType::Suspend,
            self::Ban => ModerationActionType::Ban,
        };
    }

    /** Warnings and permanent bans carry no end date; restrictions and suspensions are time-boxed. */
    public function isTimeBoxed(): bool
    {
        return $this === self::Restriction || $this === self::Suspension;
    }

    /** @return list<self> */
    public static function all(): array
    {
        return self::cases();
    }
}
