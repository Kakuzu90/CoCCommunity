<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Media\Enums\MediaStatus;
use App\Modules\Media\Exceptions\QuotaExceededException;
use App\Modules\Media\Jobs\ReapOrphans;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config(['media.disk' => 'media']);
    Storage::fake('media');
});

test('deleting media removes the original, its thumbnail, and the rows, idempotently', function (): void {
    $user = User::factory()->create();
    $media = app(MediaService::class)->store($user, UploadedFile::fake()->image('base.png', 300, 300), 'base_image');
    $media->refresh();
    $thumb = $media->variants()->firstOrFail();

    app(MediaService::class)->delete($media);

    Storage::disk('media')->assertMissing($media->path);
    Storage::disk('media')->assertMissing($thumb->path);
    expect(Media::count())->toBe(0);

    // Second call must not throw.
    app(MediaService::class)->delete($media);
    expect(Media::count())->toBe(0);
});

test('quota is enforced from the caller-supplied count', function (): void {
    expect(fn () => app(MediaService::class)->assertWithinQuota('base_image', 2))
        ->toThrow(QuotaExceededException::class);

    app(MediaService::class)->assertWithinQuota('base_image', 1); // under the limit, no throw

    expect(true)->toBeTrue();
});

test('the reaper deletes stale pending uploads but keeps recent and ready ones', function (): void {
    $user = User::factory()->create();

    $stale = app(MediaService::class)->store($user, UploadedFile::fake()->image('a.png', 100, 100), 'base_image');
    Media::whereKey($stale->id)->update(['status' => MediaStatus::Pending, 'created_at' => now()->subHours(48)]);

    $recent = app(MediaService::class)->store($user, UploadedFile::fake()->image('b.png', 100, 100), 'account');
    Media::whereKey($recent->id)->update(['status' => MediaStatus::Pending]);

    $ready = app(MediaService::class)->store($user, UploadedFile::fake()->image('c.png', 100, 100), 'account');

    app()->call([new ReapOrphans, 'handle']);

    expect(Media::whereKey($stale->id)->exists())->toBeFalse()
        ->and(Media::whereKey($recent->id)->exists())->toBeTrue()
        ->and(Media::whereKey($ready->id)->exists())->toBeTrue();
});
