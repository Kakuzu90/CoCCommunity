<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Media\Enums\MediaStatus;
use App\Modules\Media\Exceptions\MediaValidationException;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config(['media.disk' => 'media']);
    Storage::fake('media');
});

test('a valid image is stored, re-encoded, sized, and given a thumbnail', function (): void {
    $user = User::factory()->create();

    $media = app(MediaService::class)->store($user, UploadedFile::fake()->image('base.png', 800, 600), 'base_image');
    $media->refresh();

    expect($media->status)->toBe(MediaStatus::Ready)
        ->and($media->mime)->toBe('image/png')
        ->and($media->width)->toBe(800)
        ->and($media->height)->toBe(600)
        ->and($media->checksum)->not->toBeNull();

    Storage::disk('media')->assertExists($media->path);

    $thumb = $media->variants()->where('variant', 'thumb')->first();
    expect($thumb)->not->toBeNull()
        ->and(max($thumb->width, $thumb->height))->toBe(400);
    Storage::disk('media')->assertExists($thumb->path);
});

test('a file larger than the collection limit is rejected before storing', function (): void {
    config(['media.collections.base_image.max_size' => 10]);
    $user = User::factory()->create();

    expect(fn () => app(MediaService::class)->store($user, UploadedFile::fake()->image('big.png', 800, 600), 'base_image'))
        ->toThrow(MediaValidationException::class);

    expect(Media::count())->toBe(0);
});

test('content that does not match an allowed type is rejected', function (): void {
    $user = User::factory()->create();
    $fake = UploadedFile::fake()->createWithContent('layout.png', 'plain text pretending to be a png');

    expect(fn () => app(MediaService::class)->store($user, $fake, 'base_image'))
        ->toThrow(MediaValidationException::class);

    expect(Media::count())->toBe(0);
});
