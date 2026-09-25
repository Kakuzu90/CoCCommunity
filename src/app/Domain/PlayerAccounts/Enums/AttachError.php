<?php

namespace App\Domain\PlayerAccounts\Enums;

/** Why an attach/verify request could not proceed (specs/13 §3). Each maps to a precise user message. */
enum AttachError: string
{
    case InvalidTag = 'invalid_tag';
    case RateLimited = 'rate_limited';
    case AlreadyAttached = 'already_attached';
    case PlayerNotFound = 'player_not_found';
    case ApiUnavailable = 'api_unavailable';

    public function message(): string
    {
        return match ($this) {
            self::InvalidTag => 'That does not look like a valid player tag. Check it and try again.',
            self::RateLimited => 'Too many attempts. Wait a little while before trying again.',
            self::AlreadyAttached => "You've already added this account.",
            self::PlayerNotFound => 'No player exists with that tag.',
            self::ApiUnavailable => 'The game API is unavailable right now. Please try again shortly.',
        };
    }
}
