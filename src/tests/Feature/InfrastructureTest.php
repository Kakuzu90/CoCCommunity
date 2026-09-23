<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;

test('redis is not configured and database jobs wait for commit', function (): void {
    expect(config('database.redis'))->toBeNull();
    expect(config('cache.stores.redis'))->toBeNull();
    expect(config('queue.connections.redis'))->toBeNull();
    expect(config('queue.connections.database.after_commit'))->toBeTrue();
});

test('r2 signs private uploads and reads without making a network request', function (): void {
    config(['filesystems.disks.r2' => array_merge(config('filesystems.disks.r2'), [
        'key' => 'test-key',
        'secret' => 'test-secret',
        'bucket' => 'test-media',
        'region' => 'auto',
        'endpoint' => 'https://account.r2.cloudflarestorage.com',
        'url' => 'https://media.example.test',
        'use_path_style_endpoint' => true,
    ])]);

    $disk = Storage::disk('r2');
    $upload = $disk->temporaryUploadUrl('pending/image.jpg', now()->addMinutes(5));
    $read = $disk->temporaryUrl('private/image.jpg', now()->addMinutes(5));

    expect($upload['url'])->toContain('account.r2.cloudflarestorage.com/test-media/pending/image.jpg', 'X-Amz-Signature=');
    expect($read)->toContain('account.r2.cloudflarestorage.com/test-media/private/image.jpg', 'X-Amz-Signature=');
    expect($disk->url('public/image.jpg'))->toBe('https://media.example.test/public/image.jpg');
    expect(config('filesystems.disks.r2.visibility'))->toBe('private');
    expect(config('filesystems.disks.r2.throw'))->toBeTrue();
});
