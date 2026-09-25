<?php

namespace App\Domain\CocIntegration\Data;

use Carbon\CarbonImmutable;

/**
 * The outcome of `POST /players/{tag}/verifytoken` (specs/09 §9). The whole claiming system rests on
 * this. `invalid` is a normal outcome, not an error: tokens expire in minutes and are often re-copied.
 * The token itself is never stored — only this outcome (specs/09 §9).
 */
final readonly class TokenVerificationResult
{
    public function __construct(
        public string $tag,
        public bool $ok,
        public CarbonImmutable $verifiedAt,
    ) {}

    public static function ok(string $tag): self
    {
        return new self($tag, true, CarbonImmutable::now());
    }

    public static function invalid(string $tag): self
    {
        return new self($tag, false, CarbonImmutable::now());
    }
}
