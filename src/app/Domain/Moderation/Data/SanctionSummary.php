<?php

namespace App\Domain\Moderation\Data;

use App\Domain\Moderation\Enums\ReasonCode;
use App\Domain\Moderation\Enums\SanctionType;
use Carbon\CarbonInterface;

/** One sanction in a user's history, shaped for the admin detail screen (specs/12 §4). DTO only. */
final readonly class SanctionSummary
{
    public function __construct(
        public int $id,
        public SanctionType $type,
        public ReasonCode $reasonCode,
        public string $publicReason,
        public ?string $internalNote,
        public ?string $issuedByUsername,
        public ?string $liftedByUsername,
        public CarbonInterface $startsAt,
        public ?CarbonInterface $expiresAt,
        public ?CarbonInterface $liftedAt,
        public bool $active,
    ) {}
}
