<?php

declare(strict_types=1);

namespace App\Modules\Media\Jobs;

use App\Modules\Media\Enums\MediaKind;
use App\Modules\Media\Enums\MediaStatus;
use App\Modules\Media\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Finalises a pending upload. Images are re-encoded (which strips EXIF and
 * embedded exploits), downscaled if huge, and given a thumbnail derivative.
 * Anything that fails validation/decoding is marked rejected, never served.
 */
class ProcessMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $mediaId) {}

    public function handle(): void
    {
        $media = Media::find($this->mediaId);

        if ($media === null || $media->status !== MediaStatus::Pending) {
            return;
        }

        try {
            if ($media->kind === MediaKind::Image) {
                $this->processImage($media);
            }

            $media->forceFill(['status' => MediaStatus::Ready])->save();
        } catch (Throwable $e) {
            $media->forceFill(['status' => MediaStatus::Rejected])->save();
            report($e);
        }
    }

    private function processImage(Media $media): void
    {
        $disk = Storage::disk($media->disk);
        $manager = new ImageManager(new Driver);

        $image = $manager->read($disk->get($media->path));

        $maxDimension = (int) config('media.image.max_dimension');
        if (max($image->width(), $image->height()) > $maxDimension) {
            $image->scaleDown($maxDimension, $maxDimension);
        }

        $encoded = (string) $image->encodeByMediaType($media->mime);
        $disk->put($media->path, $encoded, ['visibility' => 'private']);

        $media->forceFill([
            'size' => strlen($encoded),
            'checksum' => hash('sha256', $encoded),
            'width' => $image->width(),
            'height' => $image->height(),
        ])->save();

        $this->makeThumbnail($media, $manager, $encoded);
    }

    private function makeThumbnail(Media $media, ImageManager $manager, string $sourceBytes): void
    {
        $size = (int) config('media.image.thumbnail');
        $thumb = $manager->read($sourceBytes)->scaleDown($size, $size);
        $bytes = (string) $thumb->encodeByMediaType($media->mime);

        $path = $this->thumbPath($media->path);
        Storage::disk($media->disk)->put($path, $bytes, ['visibility' => 'private']);

        Media::forceCreate([
            'parent_id' => $media->id,
            'disk' => $media->disk,
            'path' => $path,
            'kind' => MediaKind::Image,
            'variant' => 'thumb',
            'mime' => $media->mime,
            'size' => strlen($bytes),
            'width' => $thumb->width(),
            'height' => $thumb->height(),
            'checksum' => hash('sha256', $bytes),
            'status' => MediaStatus::Ready,
            'uploader_id' => $media->uploader_id,
        ]);
    }

    private function thumbPath(string $path): string
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $base = substr($path, 0, -\strlen($ext) - 1);

        return "{$base}_thumb.{$ext}";
    }
}
