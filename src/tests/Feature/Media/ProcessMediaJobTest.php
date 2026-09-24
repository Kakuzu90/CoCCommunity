<?php

use App\Domain\Auth\Models\User;
use App\Domain\Media\Contracts\MediaStorage;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Media\Events\MediaReady;
use App\Domain\Media\Jobs\ProcessMediaJob;
use App\Domain\Media\Models\Media;
use App\Domain\Media\Storage\FakeMediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Support\MediaTesting;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->storage = new FakeMediaStorage;
    $this->app->instance(MediaStorage::class, $this->storage);
});

function seedUpload(FakeMediaStorage $storage, User $user, string $collection, string $bytes, ?int $size = null): Media
{
    $media = Media::factory()->forUser($user)->uploaded()->create([
        'collection' => $collection,
        'size_bytes' => $size ?? strlen($bytes),
    ]);
    $storage->put($media->path, $bytes, 'public', 'image/png');

    return $media;
}

it('re-encodes an avatar to square webp variants and clears the quarantine original', function () {
    Event::fake([MediaReady::class]);
    $media = seedUpload($this->storage, User::factory()->create(), 'avatar', MediaTesting::pngBytes(400, 300));
    $quarantineKey = $media->path;

    ProcessMediaJob::dispatchSync($media->id);
    $media->refresh();

    expect($media->status)->toBe(MediaStatus::Ready)
        ->and($media->mime_type)->toBe('image/webp')
        ->and($media->extension)->toBe('webp')
        ->and($media->width)->toBe(400)
        ->and($media->height)->toBe(300)
        ->and($media->checksum_sha256)->not->toBeNull()
        ->and($media->variants)->toHaveCount(3);

    $variants = $media->variants->keyBy('variant');
    expect([$variants['full']->width, $variants['full']->height])->toBe([512, 512])
        ->and([$variants['card']->width, $variants['card']->height])->toBe([128, 128])
        ->and([$variants['thumb']->width, $variants['thumb']->height])->toBe([48, 48]);

    expect($this->storage->exists($quarantineKey))->toBeFalse()
        ->and($this->storage->exists($variants['full']->path))->toBeTrue()
        ->and($media->path)->toBe($variants['full']->path);

    Event::assertDispatched(MediaReady::class);
});

it('scales screenshots down preserving aspect ratio and never upscales', function () {
    $media = seedUpload($this->storage, User::factory()->create(), 'base_screenshot', MediaTesting::pngBytes(1200, 800));

    ProcessMediaJob::dispatchSync($media->id);
    $variants = $media->refresh()->variants->keyBy('variant');

    // Original 1200w is smaller than the 1600w 'full' target, so it is not upscaled.
    expect($variants['full']->width)->toBe(1200)
        ->and($variants['card']->width)->toBe(800)
        ->and($variants['thumb']->width)->toBe(320)
        ->and($variants['thumb']->height)->toBe(213);
});

it('quarantines an SVG masquerading as an image and keeps the object for review', function () {
    $media = seedUpload($this->storage, User::factory()->create(), 'avatar', MediaTesting::svgBytes());
    $key = $media->path;

    ProcessMediaJob::dispatchSync($media->id);
    $media->refresh();

    expect($media->status)->toBe(MediaStatus::Quarantined)
        ->and($media->failure_reason)->toContain('svg')
        ->and($media->variants)->toHaveCount(0)
        ->and($this->storage->exists($key))->toBeTrue();
});

it('quarantines a real type that does not match the collection allowlist', function () {
    $media = seedUpload($this->storage, User::factory()->create(), 'avatar', MediaTesting::gifBytes());

    ProcessMediaJob::dispatchSync($media->id);

    expect($media->refresh()->status)->toBe(MediaStatus::Quarantined);
});

it('quarantines bytes that are not a decodable image', function () {
    $media = seedUpload($this->storage, User::factory()->create(), 'avatar', MediaTesting::textBytes());

    ProcessMediaJob::dispatchSync($media->id);

    expect($media->refresh()->status)->toBe(MediaStatus::Quarantined);
});

it('fails an image below the minimum dimensions without quarantining it', function () {
    $media = seedUpload($this->storage, User::factory()->create(), 'avatar', MediaTesting::pngBytes(100, 100));

    ProcessMediaJob::dispatchSync($media->id);
    $media->refresh();

    expect($media->status)->toBe(MediaStatus::Failed)
        ->and($media->failure_reason)->toContain('minimum');
});

it('fails when the uploaded object size does not match the declared size', function () {
    $bytes = MediaTesting::pngBytes(400, 300);
    $media = seedUpload($this->storage, User::factory()->create(), 'avatar', $bytes, strlen($bytes) + 100_000);

    ProcessMediaJob::dispatchSync($media->id);

    expect($media->refresh()->status)->toBe(MediaStatus::Failed);
});

it('is a no-op for media that is already ready', function () {
    $media = Media::factory()->forUser(User::factory()->create())->ready()->create([
        'attachable_type' => 'base', 'attachable_id' => 1, 'expires_at' => null,
    ]);

    ProcessMediaJob::dispatchSync($media->id);

    expect($media->refresh()->variants)->toHaveCount(0);
});
