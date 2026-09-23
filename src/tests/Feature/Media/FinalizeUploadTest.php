<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Media\Enums\MediaKind;
use App\Modules\Media\Enums\MediaStatus;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    config(['media.disk' => 'media']);
    Storage::fake('media');
});

function pendingUpload(User $user, string $contents): Media
{
    $path = 'base_image/'.$user->id.'/'.Str::uuid().'.png';
    Storage::disk('media')->put($path, $contents);

    return Media::forceCreate([
        'disk' => 'media',
        'path' => $path,
        'kind' => MediaKind::Image,
        'variant' => 'original',
        'mime' => 'application/octet-stream',
        'size' => 0,
        'status' => MediaStatus::Pending,
        'uploader_id' => $user->id,
    ]);
}

test('finalizing a validly uploaded object validates and processes it', function (): void {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('x.png', 120, 90);
    $media = pendingUpload($user, (string) $file->getContent());

    app(MediaService::class)->finalizeUpload($media);
    $media->refresh();

    expect($media->status)->toBe(MediaStatus::Ready)
        ->and($media->mime)->toBe('image/png')
        ->and($media->size)->toBeGreaterThan(0)
        ->and($media->variants()->count())->toBe(1);
});

test('finalizing a bad object rejects it and deletes the file', function (): void {
    $user = User::factory()->create();
    $media = pendingUpload($user, 'not really an image');

    app(MediaService::class)->finalizeUpload($media);
    $media->refresh();

    expect($media->status)->toBe(MediaStatus::Rejected);
    Storage::disk('media')->assertMissing($media->path);
});
