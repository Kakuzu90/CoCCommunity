<?php

namespace App\Domain\GameAssets\Data;

/** Outcome of publishing the pack to the bucket (specs/10 §11.2). */
final readonly class PublishReport
{
    /** @param list<string> $keys */
    public function __construct(
        public array $keys,
    ) {}

    public function count(): int
    {
        return count($this->keys);
    }
}
