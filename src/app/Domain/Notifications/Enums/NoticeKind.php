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
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::Warning, self::Restricted, self::SanctionLifted => 'moderation',
            default => 'security',
        };
    }

    public function sendsEmail(): bool
    {
        return $this !== self::EmailVerified;
    }

    public function targetRoute(): ?string
    {
        return match ($this) {
            self::PasswordChanged, self::NewSignIn => 'settings.sessions.index',
            self::EmailVerified, self::SanctionLifted => 'home',
            default => null,
        };
    }
}
