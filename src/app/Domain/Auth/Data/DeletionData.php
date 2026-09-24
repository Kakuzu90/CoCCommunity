<?php

namespace App\Domain\Auth\Data;

use Carbon\CarbonImmutable;

final readonly class DeletionData
{
    public function __construct(
        public bool $pending,
        public ?CarbonImmutable $requestedAt,
        public ?CarbonImmutable $scheduledAt,
    ) {}
}
