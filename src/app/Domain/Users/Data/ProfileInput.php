<?php

namespace App\Domain\Users\Data;

/**
 * Validated profile-edit input (specs/07 `profiles`). A typed DTO, not a request array, crosses into
 * the service — the Form Request has already validated and shaped it.
 */
final readonly class ProfileInput
{
    /**
     * @param  list<string>  $languages  ISO-639-1, at most 3
     * @param  array<string, string>  $socials  handle/URL per platform (youtube, twitch, discord, x)
     */
    public function __construct(
        public ?string $displayName,
        public ?string $bio,
        public ?string $countryCode,
        public array $languages,
        public ?string $timezone,
        public array $socials,
    ) {}
}
