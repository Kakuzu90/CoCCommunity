<?php

declare(strict_types=1);

namespace App\Modules\CocIntegration\Contracts;

use App\Modules\CocIntegration\DTOs\PlayerData;
use App\Modules\CocIntegration\Exceptions\ClashApiUnavailableException;
use App\Modules\CocIntegration\Exceptions\PlayerNotFoundException;
use App\Modules\CocIntegration\Exceptions\RateLimitedException;

/**
 * Anti-corruption boundary for the official Clash of Clans API. Feature code
 * depends on this contract and the PlayerData DTO — never on HTTP details.
 */
interface ClashClient
{
    /**
     * Verify a player owns the account by checking the in-game API token.
     * This is the only accepted proof of ownership.
     *
     * @throws PlayerNotFoundException
     * @throws RateLimitedException
     * @throws ClashApiUnavailableException
     */
    public function verifyToken(string $tag, string $token): bool;

    /**
     * Fetch current player data for a tag.
     *
     * @throws PlayerNotFoundException
     * @throws RateLimitedException
     * @throws ClashApiUnavailableException
     */
    public function fetchPlayer(string $tag): PlayerData;
}
