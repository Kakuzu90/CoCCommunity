<?php

namespace App\Domain\Media\Contracts;

/**
 * The only seam that knows a storage provider exists. One S3-compatible implementation
 * (MinIO locally, R2 in production) plus a fake for tests (specs/10 §2.1, NFR-MAINT-5).
 */
interface MediaStorage
{
    /**
     * A presigned PUT the browser uploads to directly, constrained by content type and size.
     *
     * @return array{url: string, headers: array<string, string>}
     */
    public function presignedPut(string $path, string $contentType, int $maxSize, int $ttl): array;

    /** A short-lived signed GET for private/pending objects. */
    public function presignedGet(string $path, int $ttl): string;

    /** Object size in bytes, or null when the object does not exist. */
    public function size(string $path): ?int;

    public function exists(string $path): bool;

    /** Stream an object down to a worker-local file. */
    public function download(string $path, string $localPath): void;

    public function put(string $path, string $contents, string $visibility, string $contentType): void;

    public function delete(string ...$paths): void;
}
