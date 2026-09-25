<?php

namespace App\Domain\PlayerAccounts\Data;

use App\Domain\PlayerAccounts\Enums\VerificationOutcome;
use App\Domain\PlayerAccounts\Services\AccountAttachService;

/**
 * Outcome of {@see AccountAttachService::verify()}. `superseded` is true when a token verification
 * transferred the tag away from a previous verified holder (specs/13 §3.1).
 */
final readonly class VerificationResult
{
    public function __construct(
        public VerificationOutcome $outcome,
        public bool $superseded = false,
        public ?int $accountId = null,
    ) {}

    public function verified(): bool
    {
        return $this->outcome === VerificationOutcome::Verified;
    }
}
