<?php

namespace App\Domain\PlayerAccounts\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Fired after a tag is verified (specs/13 §3.1 step 7). The seam other modules hang off: full profile
 * sync, clan tracking and search indexing subscribe here in their own Phase 2/3 tasks. Carries ids only.
 */
final readonly class CocAccountVerified implements ShouldDispatchAfterCommit
{
    public function __construct(
        public int $accountId,
        public int $userId,
        public ?int $previousHolderId = null,
    ) {}
}
