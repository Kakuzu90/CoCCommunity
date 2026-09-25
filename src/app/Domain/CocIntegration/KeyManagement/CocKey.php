<?php

namespace App\Domain\CocIntegration\KeyManagement;

/**
 * One API key from the pool. `id` is a short, non-reversible digest used in logs, health output and
 * rotation state so the secret `token` is never written anywhere (specs/09 §3 "never logged"). This
 * type is internal to the module — a token never leaves it inside a public DTO.
 */
final readonly class CocKey
{
    public function __construct(
        public string $id,
        public string $token,
    ) {}

    public static function fromToken(string $token): self
    {
        return new self(substr(hash('sha256', $token), 0, 8), $token);
    }
}
