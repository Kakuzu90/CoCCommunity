<?php

namespace App\Domain\CocIntegration\KeyManagement;

use App\Domain\CocIntegration\Exceptions\CocApiException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Log;

/**
 * The pool of IP-bound API keys (specs/09 §3). Selection is round-robin across healthy keys so per-key
 * limits are spread; a key that fails on a bad key or IP binding is pulled from rotation and an alert
 * fires. Health lives in the cache, shared across workers. Tokens are never logged — only key ids are.
 */
final class CocKeyPool
{
    /** @var list<CocKey> */
    private array $keys;

    /** @param list<string> $tokens */
    public function __construct(array $tokens, private readonly Cache $cache)
    {
        $this->keys = array_map(CocKey::fromToken(...), $tokens);
    }

    /**
     * The next healthy key in round-robin order.
     *
     * @throws CocApiException when the pool has no healthy key — the caller degrades to cache/snapshots.
     */
    public function next(): CocKey
    {
        $healthy = $this->healthy();

        if ($healthy === []) {
            throw CocApiException::noHealthyKey();
        }

        $cursor = (int) $this->cache->increment('coc:key:cursor');

        return $healthy[$cursor % count($healthy)];
    }

    /** Pull a key from rotation and page an operator: a bad key or IP binding breaks every call. */
    public function markUnhealthy(string $keyId, string $reason): void
    {
        $this->cache->put(
            $this->healthKey($keyId),
            $reason,
            (int) config('coc.keys.unhealthy_ttl'),
        );

        Log::channel(config('logging.default'))->critical('CoC API key marked unhealthy', [
            'key_id' => $keyId,
            'reason' => $reason,
        ]);
    }

    public function markHealthy(string $keyId): void
    {
        $this->cache->forget($this->healthKey($keyId));
    }

    public function isHealthy(string $keyId): bool
    {
        return ! $this->cache->has($this->healthKey($keyId));
    }

    public function total(): int
    {
        return count($this->keys);
    }

    public function healthyCount(): int
    {
        return count($this->healthy());
    }

    /**
     * A log/health-safe view of the pool: ids and their state, never the tokens.
     *
     * @return array{total: int, healthy: int, keys: array<string, string>}
     */
    public function snapshot(): array
    {
        $keys = [];
        foreach ($this->keys as $key) {
            $keys[$key->id] = $this->isHealthy($key->id) ? 'healthy' : 'unhealthy';
        }

        return ['total' => $this->total(), 'healthy' => $this->healthyCount(), 'keys' => $keys];
    }

    /** @return list<CocKey> */
    private function healthy(): array
    {
        return array_values(array_filter($this->keys, fn (CocKey $k): bool => $this->isHealthy($k->id)));
    }

    private function healthKey(string $keyId): string
    {
        return "coc:key:{$keyId}:unhealthy";
    }
}
