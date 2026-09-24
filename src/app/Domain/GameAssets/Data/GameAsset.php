<?php

namespace App\Domain\GameAssets\Data;

/**
 * A resolved game asset: a URL to the unmodified artwork plus its accessible name, or — when the
 * category is switched off or the asset is unknown — no URL, so the caller renders our own
 * placeholder. The name is always present: a game asset never carries meaning alone (specs/18 §2.3).
 */
final readonly class GameAsset
{
    public function __construct(
        public string $category,
        public string $name,
        public ?string $url = null,
        public ?string $slug = null,
    ) {}

    /** No artwork to serve (kill switch on, or the asset is not in the pack) → render a placeholder. */
    public function isPlaceholder(): bool
    {
        return $this->url === null;
    }
}
