<?php

namespace App\Domain\Moderation\Data;

use App\Domain\Auth\Enums\UserStatus;
use App\Domain\Moderation\Enums\SanctionType;
use Carbon\CarbonInterface;

/** Outcome of applying or lifting a sanction — enough for the controller to build its flash message. */
final readonly class SanctionResult
{
    public function __construct(
        public int $sanctionId,
        public string $targetUsername,
        public ?SanctionType $type,
        public UserStatus $beforeStatus,
        public UserStatus $afterStatus,
        public ?CarbonInterface $expiresAt,
    ) {}
}
