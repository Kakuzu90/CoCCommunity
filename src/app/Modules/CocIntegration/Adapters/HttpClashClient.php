<?php

declare(strict_types=1);

namespace App\Modules\CocIntegration\Adapters;

use App\Modules\CocIntegration\Contracts\ClashClient;
use App\Modules\CocIntegration\DTOs\PlayerData;
use App\Modules\CocIntegration\Exceptions\ClashApiException;
use App\Modules\CocIntegration\Exceptions\ClashApiUnavailableException;
use App\Modules\CocIntegration\Exceptions\PlayerNotFoundException;
use App\Modules\CocIntegration\Exceptions\RateLimitedException;
use App\Modules\CocIntegration\Support\Tag;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Live adapter for the official API. Retries transient failures with backoff
 * and maps HTTP status codes onto the module's typed exceptions so callers
 * never see raw responses.
 */
final class HttpClashClient implements ClashClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $token,
        private readonly int $timeout,
        private readonly int $retries,
        private readonly int $playerCacheTtl,
    ) {}

    public function verifyToken(string $tag, string $token): bool
    {
        $tag = Tag::normalize($tag);

        $response = $this->request()->post(
            "/players/{$this->encode($tag)}/verifytoken",
            ['token' => $token],
        );

        $this->guard($response, $tag);

        return $response->json('status') === 'ok';
    }

    public function fetchPlayer(string $tag): PlayerData
    {
        $tag = Tag::normalize($tag);

        return Cache::remember(
            "coc:player:{$tag}",
            $this->playerCacheTtl,
            function () use ($tag): PlayerData {
                $response = $this->request()->get("/players/{$this->encode($tag)}");
                $this->guard($response, $tag);

                return PlayerData::fromApi($response->json());
            },
        );
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->token)
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry($this->retries, 200, throw: false);
    }

    private function encode(string $tag): string
    {
        return rawurlencode($tag);
    }

    private function guard(Response $response, string $tag): void
    {
        if ($response->successful()) {
            return;
        }

        throw match (true) {
            $response->status() === 404 => new PlayerNotFoundException($tag),
            $response->status() === 429 => new RateLimitedException,
            $response->status() >= 500 => new ClashApiUnavailableException,
            default => new ClashApiException("Clash of Clans API error ({$response->status()})."),
        };
    }
}
