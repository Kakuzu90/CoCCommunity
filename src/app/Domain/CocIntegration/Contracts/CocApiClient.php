<?php

namespace App\Domain\CocIntegration\Contracts;

use App\Domain\CocIntegration\Data\PlayerData;
use App\Domain\CocIntegration\Data\TokenVerificationResult;
use App\Domain\CocIntegration\Enums\CocRequestPriority;
use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Support\ValueObjects\PlayerTag;

/**
 * The one seam to `api.clashofclans.com` (specs/09 §1). Every service, job and test depends on this
 * interface and on our DTOs, never on the API's JSON or HTTP codes. Three implementations sit behind
 * it, wired as Cached(Throttled(Http)) — or the Fake in tests and local dev.
 *
 * Clan lookup and static reference data (leagues/locations) are deliberately out of this Phase 2
 * client; they arrive with the Phase 4 clan work (specs/09 §2).
 */
interface CocApiClient
{
    /**
     * Fetch a player. Priority chooses the rate bucket: user-triggered lookups are Interactive.
     *
     * @throws CocApiException on not-found, throttling, maintenance, an open circuit or key failure.
     */
    public function player(PlayerTag $tag, CocRequestPriority $priority = CocRequestPriority::Interactive): PlayerData;

    /**
     * Verify in-game ownership. `invalid` is returned as a result, not thrown; only transport-level
     * failures raise {@see CocApiException}. The token is never stored or logged (specs/09 §9).
     *
     * @throws CocApiException
     */
    public function verifyToken(PlayerTag $tag, string $token): TokenVerificationResult;
}
