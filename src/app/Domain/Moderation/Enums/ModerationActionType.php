<?php

namespace App\Domain\Moderation\Enums;

/**
 * The verb recorded on a `moderation_actions` row (specs/07). The full taxonomy in the spec spans
 * content and marketplace actions that arrive with later phases; Admin v1 emits only the user-sanction
 * verbs below. Kept as a backed enum so the column value is validated at write time.
 */
enum ModerationActionType: string
{
    case Warn = 'warn';
    case Restrict = 'restrict';
    case Suspend = 'suspend';
    case Ban = 'ban';
    case Unban = 'unban';
    case Dismiss = 'dismiss';
    case TransferOwnership = 'transfer_ownership';

    public function label(): string
    {
        return match ($this) {
            self::Warn => 'Warn',
            self::Restrict => 'Restrict',
            self::Suspend => 'Suspend',
            self::Ban => 'Ban',
            self::Unban => 'Lift sanction',
            self::Dismiss => 'Dismiss',
            self::TransferOwnership => 'Transfer ownership',
        };
    }
}
