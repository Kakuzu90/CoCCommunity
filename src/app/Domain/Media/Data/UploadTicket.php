<?php

namespace App\Domain\Media\Data;

final readonly class UploadTicket
{
    /** @param array<string, string> $headers */
    public function __construct(
        public string $mediaUlid,
        public string $uploadUrl,
        public array $headers,
        public int $expiresIn,
        public int $maxSize,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'media_ulid' => $this->mediaUlid,
            'upload_url' => $this->uploadUrl,
            'headers' => $this->headers,
            'expires_in' => $this->expiresIn,
            'max_size' => $this->maxSize,
        ];
    }
}
