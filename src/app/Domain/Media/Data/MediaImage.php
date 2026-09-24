<?php

namespace App\Domain\Media\Data;

/**
 * Public, render-ready view of a processed image (specs/10 §5). Other modules receive this from the
 * MediaLibrary and never touch the Media model. `variants` maps a variant name (full/card/thumb) to
 * its public CDN URL; `url()` picks the requested variant or the largest available as a fallback.
 */
final readonly class MediaImage
{
    /** @param array<string, string> $variants variant name => public URL, largest first */
    public function __construct(
        public string $ulid,
        public string $collection,
        public ?int $width,
        public ?int $height,
        public array $variants,
    ) {}

    public function url(?string $variant = null): ?string
    {
        if ($variant !== null && isset($this->variants[$variant])) {
            return $this->variants[$variant];
        }

        $first = array_key_first($this->variants);

        return $first === null ? null : $this->variants[$first];
    }

    public function isEmpty(): bool
    {
        return $this->variants === [];
    }
}
