<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Actions;

use App\Modules\CocIntegration\Support\Tag;
use App\Modules\PlayerAccounts\Jobs\VerifyCocToken;

/**
 * Entry point for the claim flow: normalises the tag, then queues the token
 * check. The API is never called in the request path — the job does the work
 * and, on success, grants ownership (creating or transferring the account).
 */
class RequestOwnershipVerification
{
    public function handle(int $userId, string $rawTag, string $token): void
    {
        $tag = Tag::normalize($rawTag);

        VerifyCocToken::dispatch($userId, $tag, $token);
    }
}
