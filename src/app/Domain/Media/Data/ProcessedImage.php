<?php

namespace App\Domain\Media\Data;

final readonly class ProcessedImage
{
    /** @param list<Rendition> $renditions */
    public function __construct(
        public int $originalWidth,
        public int $originalHeight,
        public array $renditions,
    ) {}
}
