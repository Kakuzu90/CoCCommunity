<?php

namespace App\Domain\CocIntegration\Exceptions;

use App\Domain\CocIntegration\Enums\CocErrorReason;
use RuntimeException;
use Throwable;

/**
 * The single failure type the CoC client raises (specs/09 §7). It carries a classified reason so
 * callers branch on intent, not on Supercell's HTTP codes. A token verification that returns
 * `invalid` is NOT an exception — it is an ordinary TokenVerificationResult (specs/09 §9).
 */
final class CocApiException extends RuntimeException
{
    public function __construct(
        public readonly CocErrorReason $reason,
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?int $retryAfter = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function notFound(string $tag): self
    {
        return new self(CocErrorReason::NotFound, "No player with tag {$tag}.", 404);
    }

    public static function throttled(?int $retryAfter): self
    {
        return new self(CocErrorReason::Throttled, 'The game API throttled the request.', 429, $retryAfter);
    }

    public static function maintenance(?int $retryAfter): self
    {
        return new self(CocErrorReason::Maintenance, 'The game API is in maintenance.', 503, $retryAfter);
    }

    public static function circuitOpen(): self
    {
        return new self(CocErrorReason::CircuitOpen, 'The game API circuit is open; serving cached data only.');
    }

    public static function noHealthyKey(): self
    {
        return new self(CocErrorReason::NoHealthyKey, 'No healthy CoC API key is available.');
    }

    public function isNotFound(): bool
    {
        return $this->reason === CocErrorReason::NotFound;
    }

    /** The degradation contract: everything except a genuine not-found should fall back, not error out. */
    public function shouldDegrade(): bool
    {
        return $this->reason->isTransient();
    }
}
