<?php

namespace App\Support\Health;

/** One named check plus a human message and structured detail. */
final readonly class HealthCheck
{
    /** @param array<string, mixed> $meta */
    public function __construct(
        public string $name,
        public HealthStatus $status,
        public string $message,
        public array $meta = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'message' => $this->message,
            ...($this->meta === [] ? [] : ['meta' => $this->meta]),
        ];
    }
}
