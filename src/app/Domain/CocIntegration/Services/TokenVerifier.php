<?php

namespace App\Domain\CocIntegration\Services;

use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\Data\TokenVerificationResult;
use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Support\ValueObjects\PlayerTag;

/**
 * Verify in-game ownership of a tag (specs/09 §9) — the seam the Phase 2 attach flow builds on. It
 * returns the outcome; `invalid` is normal, not an error. The token lives only for this call and is
 * never persisted or logged. Per-user attempt limits and the claim record belong to the attach task,
 * which owns coc_account_claims; this class stays a pure verification call.
 */
final readonly class TokenVerifier
{
    public function __construct(private CocApiClient $client) {}

    /**
     * @throws CocApiException on a transport-level failure (not on an invalid token).
     */
    public function verify(PlayerTag $tag, string $token): TokenVerificationResult
    {
        return $this->client->verifyToken($tag, $token);
    }
}
