<?php

namespace App\Domain\GameAssets\Data;

/** Outcome of publishing a pack version to the bucket (specs/10 §11.2). */
final readonly class PublishReport
{
    /** @param list<string> $keys */
    public function __construct(
        public int $version,
        public array $keys,
    ) {}

    public function count(): int
    {
        return count($this->keys);
    }
}
