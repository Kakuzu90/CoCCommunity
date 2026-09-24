<?php

namespace App\Domain\Media\Enums;

// Derived rendition names — the media_variants.variant domain (specs/07).
enum MediaVariant: string
{
    case Thumb = 'thumb';
    case Card = 'card';
    case Full = 'full';
    case Poster = 'poster';
    case Video720p = 'video_720p';
}
