<?php

namespace App\Domain\Media\Storage;

use App\Domain\Media\Contracts\MediaStorage;
use App\Domain\Media\Enums\MediaVisibility;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * S3-compatible storage: MinIO locally, Cloudflare R2 in production. The provider is chosen
 * entirely by config('media.disk') → config/filesystems.php; nothing here names one.
 */
class S3MediaStorage implements MediaStorage
{
    private function disk(): Filesystem
    {
        return Storage::disk(config('media.disk'));
    }

    public function presignedPut(string $path, string $contentType, int $maxSize, int $ttl): array
    {
        $result = $this->disk()->temporaryUploadUrl($path, now()->addSeconds($ttl), [
            'ContentType' => $contentType,
        ]);

        return [
            'url' => $result['url'],
            'headers' => array_merge($result['headers'] ?? [], ['Content-Type' => $contentType]),
        ];
    }

    public function presignedGet(string $path, int $ttl): string
    {
        return $this->disk()->temporaryUrl($path, now()->addSeconds($ttl));
    }

    public function size(string $path): ?int
    {
        $disk = $this->disk();

        return $disk->exists($path) ? $disk->size($path) : null;
    }

    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    public function download(string $path, string $localPath): void
    {
        $stream = $this->disk()->readStream($path);
        if ($stream === null) {
            throw new RuntimeException("Storage object not readable: {$path}");
        }

        $local = fopen($localPath, 'wb');
        if ($local === false) {
            fclose($stream);
            throw new RuntimeException("Cannot open local file: {$localPath}");
        }

        stream_copy_to_stream($stream, $local);
        fclose($stream);
        fclose($local);
    }

    public function put(string $path, string $contents, string $visibility, string $contentType): void
    {
        // Public objects are immutable (keyed by ULID); private objects must never be cached.
        $cacheControl = $visibility === MediaVisibility::Public->value
            ? 'public, max-age=31536000, immutable'
            : 'private, no-store';

        $this->disk()->put($path, $contents, [
            'ContentType' => $contentType,
            'CacheControl' => $cacheControl,
        ]);
    }

    public function delete(string ...$paths): void
    {
        $this->disk()->delete($paths);
    }
}
