<?php

namespace App\Domain\Notifications\Data;

use Carbon\CarbonImmutable;

final readonly class NotificationData
{
    public function __construct(
        public string $id,
        public string $title,
        public string $message,
        public string $category,
        public bool $unread,
        public CarbonImmutable $createdAt,
        public ?string $target,
    ) {}
}
