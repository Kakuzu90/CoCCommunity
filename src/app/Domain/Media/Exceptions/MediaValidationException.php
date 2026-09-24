<?php

namespace App\Domain\Media\Exceptions;

use RuntimeException;

/**
 * Thrown when uploaded bytes fail validation. `suspicious` distinguishes a benign failure
 * (wrong size, undecodable) from one that suggests intent (MIME mismatch, polyglot) which
 * routes the media to `quarantined` rather than `failed` (specs/10 §4).
 */
class MediaValidationException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        public readonly bool $suspicious = false,
    ) {
        parent::__construct($reason);
    }

    public static function suspicious(string $reason): self
    {
        return new self($reason, true);
    }
}
