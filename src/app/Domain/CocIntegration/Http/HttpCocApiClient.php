<?php

namespace App\Domain\CocIntegration\Http;

use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\Data\PlayerData;
use App\Domain\CocIntegration\Data\TokenVerificationResult;
use App\Domain\CocIntegration\Enums\CocErrorReason;
use App\Domain\CocIntegration\Enums\CocRequestPriority;
use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Domain\CocIntegration\KeyManagement\CocKey;
use App\Domain\CocIntegration\KeyManagement\CocKeyPool;
use App\Support\ValueObjects\PlayerTag;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\Response;

/**
 * The only class that talks to api.clashofclans.com (specs/09 §1). It draws a key from the pool, maps
 * the response into our DTOs and classifies every failure into a {@see CocApiException}. A 403 (bad key
 * or IP binding) pulls that key from rotation and retries once with another key (specs/09 §7). Rate
 * limiting, the circuit breaker and the request log are added by the decorators around this class.
 */
final class HttpCocApiClient implements CocApiClient
{
    public function __construct(
        private readonly Http $http,
        private readonly CocKeyPool $keys,
    ) {}

    public function player(PlayerTag $tag, CocRequestPriority $priority = CocRequestPriority::Interactive): PlayerData
    {
        $timeout = $priority === CocRequestPriority::Manual
            ? (int) config('coc.sync.manual_timeout_seconds')
            : (int) config('coc.timeouts.total');
        $json = $this->get('/players/'.$tag->encoded(), $tag->value, $timeout);

        if (! isset($json['tag'])) {
            throw new CocApiException(CocErrorReason::Malformed, "Malformed player payload for {$tag->value}.", 200);
        }

        return PlayerData::fromArray($json, CarbonImmutable::now());
    }

    public function verifyToken(PlayerTag $tag, string $token): TokenVerificationResult
    {
        $json = $this->post('/players/'.$tag->encoded().'/verifytoken', ['token' => $token], $tag->value);

        $status = $json['status'] ?? null;
        if ($status !== 'ok' && $status !== 'invalid') {
            throw new CocApiException(CocErrorReason::Malformed, "Malformed verifytoken payload for {$tag->value}.", 200);
        }

        return $status === 'ok'
            ? TokenVerificationResult::ok($tag->value)
            : TokenVerificationResult::invalid($tag->value);
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $path, string $tagForMessage, int $timeout): array
    {
        return $this->send('GET', $path, null, $tagForMessage, $timeout);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function post(string $path, array $body, string $tagForMessage): array
    {
        return $this->send('POST', $path, $body, $tagForMessage, (int) config('coc.timeouts.total'));
    }

    /**
     * Send with one key-failure retry: a 403 marks the current key unhealthy and tries the next.
     *
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, ?array $body, string $tagForMessage, int $timeout): array
    {
        $attempts = 0;

        while (true) {
            $attempts++;
            $key = $this->keys->next();

            try {
                $response = $this->dispatch($method, $path, $body, $key, $timeout);
            } catch (ConnectionException $e) {
                throw new CocApiException(CocErrorReason::Timeout, 'The game API did not respond in time.', null, null, $e);
            }

            if ($response->successful()) {
                /** @var array<string, mixed> $json */
                $json = $response->json() ?? [];

                return $json;
            }

            $reason = $this->classify($response);

            if ($reason->isKeyFailure()) {
                $this->keys->markUnhealthy($key->id, $reason->value);
                if ($attempts < 2 && $this->keys->healthyCount() > 0) {
                    continue; // retry once with another key
                }
            }

            throw $this->exception($reason, $response, $tagForMessage);
        }
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function dispatch(string $method, string $path, ?array $body, CocKey $key, int $timeout): Response
    {
        $request = $this->http
            ->baseUrl((string) config('coc.base_url'))
            ->withToken($key->token)
            ->acceptJson()
            ->connectTimeout((int) config('coc.timeouts.connect'))
            ->timeout($timeout);

        return $method === 'POST'
            ? $request->post($path, $body ?? [])
            : $request->get($path);
    }

    private function classify(Response $response): CocErrorReason
    {
        $reason = (string) ($response->json('reason') ?? '');

        return match ($response->status()) {
            404 => CocErrorReason::NotFound,
            403 => str_contains($reason, 'invalidIp') ? CocErrorReason::InvalidIp : CocErrorReason::AccessDenied,
            429 => CocErrorReason::Throttled,
            503 => CocErrorReason::Maintenance,
            default => CocErrorReason::ServerError,
        };
    }

    private function exception(CocErrorReason $reason, Response $response, string $tag): CocApiException
    {
        $retryAfter = ($h = $response->header('Retry-After')) !== '' ? (int) $h : null;

        return match ($reason) {
            CocErrorReason::NotFound => CocApiException::notFound($tag),
            CocErrorReason::Throttled => CocApiException::throttled($retryAfter),
            CocErrorReason::Maintenance => CocApiException::maintenance($retryAfter),
            default => new CocApiException($reason, "CoC API error ({$reason->value}) for {$tag}.", $response->status()),
        };
    }
}
