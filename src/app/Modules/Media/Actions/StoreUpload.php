<?php

declare(strict_types=1);

namespace App\Modules\Media\Actions;

use App\Models\User;
use App\Modules\Media\Enums\MediaKind;
use App\Modules\Media\Enums\MediaStatus;
use App\Modules\Media\Jobs\ProcessMedia;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Support\FileSignature;
use App\Modules\Media\Support\MediaGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Server-side upload path: validate the bytes, store the object privately as a
 * pending row, then queue processing. Used by the app's own upload UI; the R2
 * direct-upload path is reserveUpload()/finalizeUpload() on MediaService.
 */
class StoreUpload
{
    public function handle(User $uploader, UploadedFile $file, string $collection): Media
    {
        $config = MediaGuard::collection($collection);

        $bytes = (string) file_get_contents($file->getRealPath());
        $size = strlen($bytes);
        $mime = MediaGuard::validate($bytes, $size, $config);

        $disk = (string) config('media.disk');
        $path = $this->buildPath($collection, $uploader->id, $mime);

        Storage::disk($disk)->put($path, $bytes, ['visibility' => 'private']);

        $media = Media::forceCreate([
            'disk' => $disk,
            'path' => $path,
            'kind' => MediaKind::from((string) $config['kind']),
            'variant' => 'original',
            'mime' => $mime,
            'size' => $size,
            'checksum' => hash('sha256', $bytes),
            'status' => MediaStatus::Pending,
            'uploader_id' => $uploader->id,
        ]);

        ProcessMedia::dispatch($media->id);

        return $media;
    }

    private function buildPath(string $collection, int $uploaderId, string $mime): string
    {
        return sprintf('%s/%d/%s.%s', $collection, $uploaderId, Str::uuid(), FileSignature::extensionFor($mime));
    }
}
