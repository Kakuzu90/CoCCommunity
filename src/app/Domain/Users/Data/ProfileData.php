<?php

namespace App\Domain\Users\Data;

use App\Domain\Media\Data\MediaImage;

/**
 * Render-ready view of a profile handed to Presentation (specs/07). The avatar is already resolved
 * to a MediaImage (or null) so views never resolve media themselves. `bio` is the raw stored text;
 * Blade escapes it on render (specs/07: stored unescaped, escaped on render).
 */
final readonly class ProfileData
{
    /**
     * @param  list<string>  $languages
     * @param  array<string, string>  $socials
     */
    public function __construct(
        public int $userId,
        public ?string $displayName,
        public ?string $bio,
        public ?string $countryCode,
        public array $languages,
        public ?string $timezone,
        public array $socials,
        public ?MediaImage $avatar,
    ) {}
}
