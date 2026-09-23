<?php

declare(strict_types=1);

namespace App\Modules\Media\Services;

use App\Models\User;
use App\Modules\Media\Actions\DeleteMedia;
use App\Modules\Media\Actions\FinalizeUpload;
use App\Modules\Media\Actions\StoreUpload;
use App\Modules\Media\Enums\MediaKind;
use App\Modules\Media\Enums\MediaStatus;
use App\Modules\Media\Exceptions\QuotaExceededException;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Support\MediaGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Public entry point other modules use to store, serve, and delete media, so
 * they never touch the Media model or the storage disk directly.
 */
class MediaService
{
    public function __construct(
        private readonly StoreUpload $store,
        private readonly FinalizeUpload $finalize,
        private readonly DeleteMedia $delete,
    ) {}

    /**
     * Store an uploaded file server-side (validate, persist, queue processing).
     */
    public function store(User $uploader, UploadedFile $file, string $collection): Media
    {
        return $this->store->handle($uploader, $file, $collection);
    }

    /**
     * Reserve a pending record and a signed URL for a direct-to-storage (R2)
     * PUT. Only available on S3-compatible disks; use store() elsewhere.
     *
     * @return array{media: Media, url: string, headers: array<string, string>}
     */
    public function reserveUpload(User $uploader, string $collection, string $originalName): array
    {
        $config = MediaGuard::collection($collection);
        $disk = (string) config('media.disk');

        if (config("filesystems.disks.{$disk}.driver") !== 's3') {
            throw new RuntimeException("Direct uploads require an S3-compatible disk; '{$disk}' is not one.");
        }

        $ext = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'bin';
        $path = sprintf('%s/%d/%s.%s', $collection, $uploader->id, Str::uuid(), $ext);

        $media = Media::forceCreate([
            'disk' => $disk,
            'path' => $path,
            'kind' => MediaKind::from((string) $config['kind']),
            'variant' => 'original',
            'mime' => 'application/octet-stream',
            'size' => 0,
            'status' => MediaStatus::Pending,
            'uploader_id' => $uploader->id,
        ]);

        $signed = Storage::disk($disk)->temporaryUploadUrl(
            $path,
            now()->addSeconds((int) config('media.signed_url_ttl')),
        );

        return ['media' => $media, 'url' => $signed['url'], 'headers' => $signed['headers']];
    }

    /**
     * Validate and process an object uploaded via a reserved signed URL.
     */
    public function finalizeUpload(Media $media): void
    {
        $this->finalize->handle($media);
    }

    public function delete(Media $media): void
    {
        $this->delete->handle($media);
    }

    /**
     * Guard a per-entity file count before storing (the caller knows the count).
     */
    public function assertWithinQuota(string $collection, int $currentCount): void
    {
        $max = (int) MediaGuard::collection($collection)['max_files'];

        if ($currentCount >= $max) {
            throw new QuotaExceededException($collection, $max);
        }
    }

    /** @return array<string, mixed> */
    public function limits(string $collection): array
    {
        return MediaGuard::collection($collection);
    }

    /**
     * A short-lived signed URL for private/pending media (works on R2 and on
     * the local disk when `serve` is enabled).
     */
    public function temporaryUrl(Media $media, ?int $seconds = null): string
    {
        $seconds ??= (int) config('media.signed_url_ttl');

        return Storage::disk($media->disk)->temporaryUrl($media->path, now()->addSeconds($seconds));
    }

    /**
     * A permanent (CDN) URL — only for media approved for public access.
     */
    public function url(Media $media): string
    {
        return Storage::disk($media->disk)->url($media->path);
    }
}
