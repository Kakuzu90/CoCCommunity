<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Events;

use Illuminate\Foundation\Events\Dispatchable;

class AccountOwnershipTransferred
{
    use Dispatchable;

    public function __construct(
        public readonly int $accountId,
        public readonly ?int $previousOwnerId,
        public readonly int $newOwnerId,
    ) {}
}
