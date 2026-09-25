<?php

namespace App\Domain\CocIntegration\Services;

use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\Data\PlayerData;
use App\Domain\CocIntegration\Enums\CocRequestPriority;
use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Support\ValueObjects\PlayerTag;

/**
 * Fetch a player through the client stack. This is the public seam other modules use for the "Is this
 * you?" preview and read-through views (specs/09 §9); they depend on this and the DTO, never on the
 * client internals. Failure handling — caching, staleness, the circuit — lives below in the decorators.
 */
final readonly class PlayerLookup
{
    public function __construct(private CocApiClient $client) {}

    /**
     * @throws CocApiException when the tag does not exist or the API is unreachable with no stale copy.
     */
    public function find(PlayerTag $tag, CocRequestPriority $priority = CocRequestPriority::Interactive): PlayerData
    {
        return $this->client->player($tag, $priority);
    }
}
