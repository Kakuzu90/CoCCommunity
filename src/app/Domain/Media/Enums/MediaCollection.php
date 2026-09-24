<?php

namespace App\Domain\Media\Enums;

// Every uploadable collection (specs/07 media.collection). Only those configured in
// config('media.collections') accept uploads in this phase; video/evidence land in later phases.
enum MediaCollection: string
{
    case Avatar = 'avatar';
    case AccountImage = 'account_image';
    case BaseScreenshot = 'base_screenshot';
    case BaseVideo = 'base_video';
    case Evidence = 'evidence';
    case Portfolio = 'portfolio';

    /** @return array<string, mixed>|null */
    public function config(): ?array
    {
        return config('media.collections.'.$this->value);
    }

    public function isEnabled(): bool
    {
        return $this->config() !== null;
    }
}
