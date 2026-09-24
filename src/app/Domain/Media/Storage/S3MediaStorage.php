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

    /**
     * The disk used to sign browser-facing URLs. SigV4 signs the `host` header, so a presigned URL
     * must be signed against the exact host the browser will call. Locally the app reaches MinIO at
     * an in-network endpoint the browser cannot use (minio:9000), so presigning is done against a
     * client whose endpoint is the browser-reachable host (`media.presign_host`, e.g.
     * localhost:9000). In production the presign host is empty and this is just the media disk —
     * R2's endpoint is public and identical for app and browser (specs/10 §2.1).
     */
    private function presignDisk(): Filesystem
    {
        $host = (string) config('media.presign_host');
        if ($host === '') {
            return $this->disk();
        }

        /** @var array<string, mixed> $config */
        $config = config('filesystems.disks.'.config('media.disk'));
        $config['endpoint'] = $host;

        return Storage::build($config);
    }

    public function presignedPut(string $path, string $contentType, int $maxSize, int $ttl): array
    {
        $result = $this->presignDisk()->temporaryUploadUrl($path, now()->addSeconds($ttl), [
            'ContentType' => $contentType,
        ]);

        return [
            'url' => $result['url'],
            'headers' => array_merge($result['headers'] ?? [], ['Content-Type' => $contentType]),
        ];
    }

    public function presignedGet(string $path, int $ttl): string
    {
        return $this->presignDisk()->temporaryUrl($path, now()->addSeconds($ttl));
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
