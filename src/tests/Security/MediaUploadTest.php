<?php

use App\Domain\Media\Enums\MediaCollection;
use App\Domain\Media\Exceptions\MediaValidationException;
use App\Domain\Media\Services\MediaValidator;
use Tests\Support\MediaTesting;

// The upload attack suite (specs/11 §Upload suite): SVG, MIME mismatch, non-image,
// 0-byte, dimension bomb. Re-encode plus these validation gates are the file-upload controls.

function validateBytes(string $bytes, MediaCollection $collection = MediaCollection::Avatar): array
{
    $path = tempnam(sys_get_temp_dir(), 'mediasec');
    file_put_contents($path, $bytes);

    try {
        return app(MediaValidator::class)->validateImage($path, $collection);
    } finally {
        @unlink($path);
    }
}

it('rejects SVG as suspicious', function () {
    validateBytes(MediaTesting::svgBytes());
})->throws(MediaValidationException::class, 'svg rejected');

it('rejects a real type outside the collection allowlist as suspicious', function () {
    try {
        validateBytes(MediaTesting::gifBytes());
        $this->fail('expected rejection');
    } catch (MediaValidationException $e) {
        expect($e->suspicious)->toBeTrue();
    }
});

it('rejects a polyglot: declared image, real bytes are text', function () {
    validateBytes(MediaTesting::textBytes());
})->throws(MediaValidationException::class);

it('rejects a zero-byte file', function () {
    validateBytes('');
})->throws(MediaValidationException::class);

it('rejects a decompression bomb by dimensions before decode', function () {
    // A 6001px-wide image is a few KB on disk but exceeds the max-dimension guard.
    validateBytes(MediaTesting::pngBytes(6001, 1));
})->throws(MediaValidationException::class, 'exceed the maximum');

it('accepts a valid in-bounds png', function () {
    expect(validateBytes(MediaTesting::pngBytes(400, 300)))
        ->toMatchArray(['mime' => 'image/png', 'width' => 400, 'height' => 300]);
});
