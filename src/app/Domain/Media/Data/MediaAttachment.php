<?php

namespace App\Domain\Media\Data;

use App\Domain\Media\Enums\MediaStatus;

final readonly class MediaAttachment
{
    public function __construct(
        public string $type,
        public int $id,
        public MediaStatus $status,
    ) {}
}
