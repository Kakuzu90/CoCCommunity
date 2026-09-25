<?php

namespace App\Domain\Bases\Data;

use App\Domain\Bases\Enums\BaseStatus;

final readonly class BaseSubmission
{
    public function __construct(
        public string $ulid,
        public string $title,
        public BaseStatus $status,
        public ?string $publishedAt,
    ) {}
}
