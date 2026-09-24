<?php

namespace App\Domain\Media\Jobs;

use App\Domain\Media\Contracts\ImageProcessor;
use App\Domain\Media\Contracts\MediaStorage;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Media\Events\MediaFailed;
use App\Domain\Media\Events\MediaReady;
use App\Domain\Media\Exceptions\MediaValidationException;
use App\Domain\Media\Models\Media;
use App\Domain\Media\Models\MediaVariant;
use App\Domain\Media\Services\MediaValidator;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Validate → re-encode → write variants → `ready`, then delete the quarantine original
 * (specs/10 §3). Takes an id, not a model (specs/20). Cleans its temp dir on failure.
 */
class ProcessMediaJob implements ShouldQueue
{
    use FoundationQueueable, Queueable;

    public int $tries = 2;

    public int $timeout = 900;

    public function __construct(public int $mediaId)
    {
        $this->onQueue(QueueName::Media->value);
    }

    public function handle(MediaStorage $storage, MediaValidator $validator, ImageProcessor $processor): void
    {
        $media = Media::find($this->mediaId);
        if ($media === null) {
            return;
        }

        // Idempotent: only an uploaded (or re-run processing) row is worked.
        if (! in_array($media->status, [MediaStatus::Uploaded, MediaStatus::Processing], true)) {
            return;
        }

        $media->status = MediaStatus::Processing;
        $media->save();

        $quarantineKey = $media->path;
        $tmpDir = $this->tmpDir($media->ulid);
        File::ensureDirectoryExists($tmpDir);

        try {
            // Pre-flight free space: original + decode + variants need headroom (specs/10 §10).
            $free = @disk_free_space($tmpDir);
            if ($free !== false && $free < $media->size_bytes * 3) {
                $this->release(120);

                return;
            }

            // Post-upload HEAD: R2 can 200 an unreadable object (specs/23 §4). Retryable.
            $size = $storage->size($quarantineKey);
            if ($size === null) {
                throw new RuntimeException('uploaded object is not readable');
            }

            $tolerance = (int) config('media.intent.size_tolerance');
            if (abs($size - $media->size_bytes) > $tolerance) {
                throw new MediaValidationException('declared size does not match the uploaded object');
            }

            $original = $tmpDir.'/original';
            $storage->download($quarantineKey, $original);
            $checksum = hash_file('sha256', $original);
            if ($checksum === false) {
                throw new RuntimeException('could not hash the downloaded object');
            }

            $validator->validateImage($original, $media->collection);
            $processed = $processor->process($original, $media->collection, $tmpDir);

            $variantKeys = [];
            foreach ($processed->renditions as $rendition) {
                $key = sprintf(
                    '%s/%s/%s/%s.webp',
                    config('media.prefixes.public'),
                    $media->collection->value,
                    $media->ulid,
                    $rendition->variant->value,
                );
                $storage->put($key, (string) file_get_contents($rendition->localPath), $media->visibility->value, 'image/webp');
                $variantKeys[$rendition->variant->value] = ['key' => $key, 'rendition' => $rendition];
            }

            if ($variantKeys === []) {
                throw new MediaValidationException('no renditions were produced');
            }
            // The canonical object the media row points at: the largest rendition available.
            $primary = $variantKeys['full'] ?? $variantKeys['card'] ?? array_values($variantKeys)[0];

            DB::transaction(function () use ($media, $variantKeys, $processed, $checksum, $primary) {
                foreach ($variantKeys as $name => $data) {
                    MediaVariant::create([
                        'media_id' => $media->id,
                        'variant' => $name,
                        'path' => $data['key'],
                        'width' => $data['rendition']->width,
                        'height' => $data['rendition']->height,
                        'size_bytes' => $data['rendition']->sizeBytes,
                        'mime_type' => 'image/webp',
                    ]);
                }

                $media->mime_type = 'image/webp';
                $media->extension = 'webp';
                $media->width = $processed->originalWidth;
                $media->height = $processed->originalHeight;
                $media->checksum_sha256 = $checksum;
                $media->path = $primary['key'];
                $media->processed_at = now();
                $media->status = MediaStatus::Ready;
                $media->save();
            });

            // The unvalidated original never survives a successful run.
            $storage->delete($quarantineKey);
            File::deleteDirectory($tmpDir);

            MediaReady::dispatch($media->id);
        } catch (MediaValidationException $e) {
            // Terminal: a bad file is not retried. Suspicious files are quarantined, not failed.
            $this->markTerminal($media, $e->suspicious ? MediaStatus::Quarantined : MediaStatus::Failed, $e->reason);
            File::deleteDirectory($tmpDir);
        }
        // Any other exception propagates so the queue retries, then falls into failed().
    }

    public function failed(?Throwable $e): void
    {
        $media = Media::find($this->mediaId);
        if ($media !== null) {
            File::deleteDirectory($this->tmpDir($media->ulid));

            if (in_array($media->status, [MediaStatus::Uploaded, MediaStatus::Processing], true)) {
                $this->markTerminal($media, MediaStatus::Failed, 'processing failed');
            }
        }
    }

    private function markTerminal(Media $media, MediaStatus $status, string $reason): void
    {
        $media->status = $status;
        $media->failure_reason = Str::limit($reason, 100, '');
        if ($status === MediaStatus::Quarantined) {
            $media->expires_at = now()->addDays(30); // retained for moderator review
        }
        $media->save();

        if ($status === MediaStatus::Failed) {
            MediaFailed::dispatch($media->id, $reason);
        }
    }

    private function tmpDir(string $ulid): string
    {
        return storage_path('app/media-tmp/'.$ulid);
    }
}
