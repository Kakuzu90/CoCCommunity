<?php

namespace App\Domain\Moderation\Enums;

/**
 * The fixed report/sanction reason taxonomy (specs/12 §2). A sanction records the reason code that
 * justified it; the code is mandatory at the service layer, not merely in the form (specs/04 §2). The
 * auto-priority a reason carries drives the report queue in Phase 3 — kept here so the taxonomy has a
 * single home, though Admin v1 only reads the label.
 */
enum ReasonCode: string
{
    case AccountTrading = 'account_trading';
    case Scam = 'scam';
    case FalseOwnership = 'false_ownership';
    case Nsfw = 'nsfw';
    case Hate = 'hate';
    case Harassment = 'harassment';
    case Impersonation = 'impersonation';
    case StolenContent = 'stolen_content';
    case OffPlatformPayment = 'off_platform_payment';
    case Spam = 'spam';
    case WrongCategory = 'wrong_category';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::AccountTrading => 'Account trading',
            self::Scam => 'Scam',
            self::FalseOwnership => 'False ownership',
            self::Nsfw => 'NSFW',
            self::Hate => 'Hate',
            self::Harassment => 'Harassment',
            self::Impersonation => 'Impersonation',
            self::StolenContent => 'Stolen content',
            self::OffPlatformPayment => 'Off-platform payment',
            self::Spam => 'Spam',
            self::WrongCategory => 'Wrong category',
            self::Other => 'Other',
        };
    }

    /** @return list<self> */
    public static function all(): array
    {
        return self::cases();
    }
}
