<?php

namespace App\Domain\Users\Data;

use Carbon\CarbonImmutable;

final readonly class PublicProfileData
{
    public function __construct(
        public string $username,
        public CarbonImmutable $joinedAt,
        public bool $verified,
        public ProfileData $profile,
        public int $basesPublished,
        public int $likesReceived,
        public int $copies,
        public bool $searchable,
    ) {}
}
