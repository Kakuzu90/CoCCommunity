<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Events;

use Illuminate\Foundation\Events\Dispatchable;

class AccountVerified
{
    use Dispatchable;

    public function __construct(
        public readonly int $accountId,
        public readonly int $userId,
    ) {}
}
