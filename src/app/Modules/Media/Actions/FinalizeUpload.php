<?php

declare(strict_types=1);

namespace App\Modules\Media\Actions;

use App\Modules\Media\Enums\MediaStatus;
use App\Modules\Media\Exceptions\MediaValidationException;
use App\Modules\Media\Jobs\ProcessMedia;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Support\MediaGuard;
use Illuminate\Support\Facades\Storage;

/**
 * Completes the R2 direct-upload path: the client has PUT the object to the
 * signed URL, we now validate the stored bytes and queue processing. An object
 * that fails validation is deleted and the row rejected.
 */
class FinalizeUpload
{
    public function handle(Media $media): void
    {
        if ($media->status !== MediaStatus::Pending) {
            return;
        }

        $disk = Storage::disk($media->disk);

        if (! $disk->exists($media->path)) {
            $media->forceFill(['status' => MediaStatus::Rejected])->save();

            return;
        }

        $collection = explode('/', $media->path)[0];

        try {
            $bytes = (string) $disk->get($media->path);
            $mime = MediaGuard::validate($bytes, $disk->size($media->path), MediaGuard::collection($collection));
        } catch (MediaValidationException $e) {
            $disk->delete($media->path);
            $media->forceFill(['status' => MediaStatus::Rejected])->save();
            report($e);

            return;
        }

        $media->forceFill([
            'mime' => $mime,
            'size' => strlen($bytes),
            'checksum' => hash('sha256', $bytes),
        ])->save();

        ProcessMedia::dispatch($media->id);
    }
}
