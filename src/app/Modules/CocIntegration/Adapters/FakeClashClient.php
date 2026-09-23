<?php

declare(strict_types=1);

namespace App\Modules\CocIntegration\Adapters;

use App\Modules\CocIntegration\Contracts\ClashClient;
use App\Modules\CocIntegration\DTOs\PlayerData;
use App\Modules\CocIntegration\Exceptions\ClashApiUnavailableException;
use App\Modules\CocIntegration\Exceptions\PlayerNotFoundException;
use App\Modules\CocIntegration\Support\Tag;

/**
 * In-memory client for local dev and tests. Never touches the network.
 * Configure it via definePlayer(); flip $unavailable to simulate an outage.
 */
final class FakeClashClient implements ClashClient
{
    /** @var array<string, PlayerData> */
    public array $players = [];

    /** @var array<string, string> tag => valid token */
    public array $tokens = [];

    public bool $unavailable = false;

    public function definePlayer(PlayerData $player, ?string $token = null): void
    {
        $this->players[$player->tag] = $player;

        if ($token !== null) {
            $this->tokens[$player->tag] = $token;
        }
    }

    public function verifyToken(string $tag, string $token): bool
    {
        $tag = Tag::normalize($tag);
        $this->guard();

        return isset($this->tokens[$tag]) && hash_equals($this->tokens[$tag], $token);
    }

    public function fetchPlayer(string $tag): PlayerData
    {
        $tag = Tag::normalize($tag);
        $this->guard();

        return $this->players[$tag] ?? throw new PlayerNotFoundException($tag);
    }

    private function guard(): void
    {
        if ($this->unavailable) {
            throw new ClashApiUnavailableException;
        }
    }
}
