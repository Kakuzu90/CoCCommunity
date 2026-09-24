<?php

namespace App\Domain\Auth\Data;

use Carbon\CarbonImmutable;

final readonly class SessionData
{
    public function __construct(
        public string $id,
        public string $device,
        public ?string $ipAddress,
        public CarbonImmutable $lastActive,
        public bool $current,
    ) {}
}
