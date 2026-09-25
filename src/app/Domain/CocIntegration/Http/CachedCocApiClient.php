<?php

namespace App\Domain\CocIntegration\Http;

use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\Data\PlayerData;
use App\Domain\CocIntegration\Data\TokenVerificationResult;
use App\Domain\CocIntegration\Enums\CocRequestPriority;
use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Support\ValueObjects\PlayerTag;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * The outermost decorator (specs/09 §5): a cache hit costs no rate-limit budget because it never reaches
 * the throttled/HTTP layers. It short-caches players, negative-caches not-found tags to stop retry
 * storms, and keeps a long-lived "last good" copy so an outage serves stale data (marked as such)
 * rather than an error — the degradation contract in specs/09 §7. Token verification is never cached:
 * it must always be a live check.
 */
final class CachedCocApiClient implements CocApiClient
{
    public function __construct(
        private readonly CocApiClient $inner,
        private readonly Cache $cache,
    ) {}

    public function player(PlayerTag $tag, CocRequestPriority $priority = CocRequestPriority::Interactive): PlayerData
    {
        $id = ltrim($tag->value, '#');

        if ($this->cache->has("coc:404:{$id}")) {
            throw CocApiException::notFound($tag->value);
        }

        $fresh = $this->cache->get("coc:player:{$id}");
        if ($fresh instanceof PlayerData) {
            return $fresh;
        }

        try {
            $player = $this->inner->player($tag, $priority);
        } catch (CocApiException $e) {
            return $this->fallback($e, $id);
        }

        $this->cache->put("coc:player:{$id}", $player, (int) config('coc.cache.player_ttl'));
        $this->cache->put("coc:player:{$id}:last", $player, (int) config('coc.cache.stale_ttl'));

        return $player;
    }

    public function verifyToken(PlayerTag $tag, string $token): TokenVerificationResult
    {
        return $this->inner->verifyToken($tag, $token);
    }

    public function refresh(PlayerTag $tag, CocRequestPriority $priority): PlayerData
    {
        $id = ltrim($tag->value, '#');
        $player = $this->inner->player($tag, $priority);
        $this->cache->forget("coc:404:{$id}");
        $this->cache->put("coc:player:{$id}", $player, (int) config('coc.cache.player_ttl'));
        $this->cache->put("coc:player:{$id}:last", $player, (int) config('coc.cache.stale_ttl'));

        return $player;
    }

    /** Drop the cached player and negative marker so the next lookup hits the API (manual refresh). */
    public function forget(PlayerTag $tag): void
    {
        $id = ltrim($tag->value, '#');
        $this->cache->forget("coc:player:{$id}");
        $this->cache->forget("coc:404:{$id}");
    }

    private function fallback(CocApiException $e, string $id): PlayerData
    {
        if ($e->isNotFound()) {
            $this->cache->put("coc:404:{$id}", true, (int) config('coc.cache.negative_ttl'));

            throw $e;
        }

        // Transient failure: serve the last good copy, flagged stale, rather than erroring out.
        $last = $this->cache->get("coc:player:{$id}:last");
        if ($e->shouldDegrade() && $last instanceof PlayerData) {
            return $last->asStale();
        }

        throw $e;
    }
}
