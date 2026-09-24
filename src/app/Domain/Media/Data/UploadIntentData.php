<?php

namespace App\Domain\Media\Data;

use App\Domain\Media\Enums\MediaCollection;

final readonly class UploadIntentData
{
    public function __construct(
        public MediaCollection $collection,
        public string $filename,
        public int $size,
        public string $declaredMime,
    ) {}
}
