<?php

namespace App\Domain\Notifications\Enums;

enum NoticeKind: string
{
    case EmailVerified = 'email_verified';
    case PasswordChanged = 'password_changed';
    case NewSignIn = 'new_sign_in';
    case Warning = 'warning';
    case Restricted = 'restricted';
    case Suspended = 'suspended';
    case Banned = 'banned';
    case SanctionLifted = 'sanction_lifted';
    case AccountVerified = 'account_verified';
    case AccountSuperseded = 'account_superseded';
    case TagReleased = 'tag_released';
    case DisputeOpened = 'dispute_opened';
    case DisputeDecided = 'dispute_decided';

    public function title(): string
    {
        return match ($this) {
            self::EmailVerified => 'Email verified',
            self::PasswordChanged => 'Your password changed',
            self::NewSignIn => 'New sign-in to your account',
            self::Warning => 'You received an account warning',
            self::Restricted => 'Your account is restricted',
            self::Suspended => 'Your account is suspended',
            self::Banned => 'Your account is banned',
            self::SanctionLifted => 'Your account sanction was lifted',
            self::AccountVerified => 'Account verified',
            self::AccountSuperseded => 'Ownership of your account changed',
            self::TagReleased => 'Account released',
            self::DisputeOpened => 'Ownership dispute opened on your account',
            self::DisputeDecided => 'An ownership dispute was decided',
        };
    }

    public function message(): string
    {
        return match ($this) {
            self::EmailVerified => 'Your email address is confirmed.',
            self::PasswordChanged => 'If you did not change your password, reset it and review your sessions.',
            self::NewSignIn => 'A sign-in used a browser that did not match your existing sessions. Review your sessions if this was not you.',
            self::Warning => 'A moderator issued a warning on your account.',
            self::Restricted => 'Some account actions are temporarily unavailable.',
            self::Suspended => 'You cannot sign in while your account is suspended.',
            self::Banned => 'You can no longer sign in to this account.',
            self::SanctionLifted => 'You can use your account again.',
            self::AccountVerified => 'Your Clash of Clans account is now verified.',
            self::AccountSuperseded => 'Someone verified ownership of this tag with an in-game token. If this was not you, your account may be compromised: secure it and contact support.',
            self::TagReleased => 'A Clash of Clans account was released from your profile.',
            self::DisputeOpened => 'Someone opened an ownership dispute for one of your verified accounts. Respond within 7 days: the fastest way to end it is to re-verify with a fresh in-game token.',
            self::DisputeDecided => 'A moderator reviewed an ownership dispute involving your account. Open your disputes to see the decision.',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::Warning, self::Restricted, self::SanctionLifted => 'moderation',
            self::AccountVerified, self::TagReleased => 'account',
            self::DisputeOpened, self::DisputeDecided => 'account',
            default => 'security',
        };
    }

    public function sendsEmail(): bool
    {
        // In-app only for the routine, non-security account events. Disputes are contested ownership,
        // so both parties get email as well as an in-app notice (specs/13 §8).
        return ! in_array($this, [self::EmailVerified, self::AccountVerified, self::TagReleased], true);
    }

    public function targetRoute(): ?string
    {
        return match ($this) {
            self::PasswordChanged, self::NewSignIn => 'settings.sessions.index',
            self::AccountVerified, self::AccountSuperseded, self::TagReleased => 'accounts.index',
            self::DisputeOpened, self::DisputeDecided => 'accounts.disputes.index',
            self::EmailVerified, self::SanctionLifted => 'home',
            default => null,
        };
    }
}
