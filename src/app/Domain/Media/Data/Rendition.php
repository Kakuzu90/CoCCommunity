<?php

namespace App\Domain\Media\Data;

use App\Domain\Media\Enums\MediaVariant;

final readonly class Rendition
{
    public function __construct(
        public MediaVariant $variant,
        public string $localPath,
        public int $width,
        public int $height,
        public int $sizeBytes,
        public string $mime,
    ) {}
}
