<?php

namespace App\Domain\Media\Services;

use App\Domain\Media\Enums\MediaCollection;
use App\Domain\Media\Exceptions\MediaValidationException;

/**
 * Untrusted-bytes gate (specs/10 §4). Detects the real type from the file signature and bounds
 * dimensions from the header *before* any full decode — the decompression-bomb defence (specs/23 §4).
 */
class MediaValidator
{
    /**
     * @return array{mime: string, width: int, height: int}
     */
    public function validateImage(string $localPath, MediaCollection $collection): array
    {
        $config = $collection->config();
        if ($config === null || ($config['kind'] ?? null) !== 'image') {
            throw new MediaValidationException('collection is not an image collection');
        }

        $head = (string) file_get_contents($localPath, false, null, 0, 1024);

        // SVG is script-capable and rejected outright, however it is labelled.
        if (preg_match('/<\?xml|<svg[\s>]/i', $head) === 1) {
            throw MediaValidationException::suspicious('svg rejected');
        }

        // Header-only read: dimensions + real type without decoding the raster.
        $info = @getimagesize($localPath);
        if ($info === false) {
            throw MediaValidationException::suspicious('file is not a decodable image');
        }

        [$width, $height] = $info;
        $mime = image_type_to_mime_type($info[2]);

        // Real MIME must be in the collection's allowlist; a mismatch signals content-type confusion.
        if (! in_array($mime, $config['declared_mimes'], true)) {
            throw MediaValidationException::suspicious("real mime {$mime} not allowed for {$collection->value}");
        }

        // Animated formats belong in the video slot, not an image collection.
        if ($mime === 'image/webp' && str_contains(substr($head, 0, 64), 'ANIM')) {
            throw new MediaValidationException('animated webp rejected');
        }
        if ($mime === 'image/png' && $this->isApng($localPath)) {
            throw new MediaValidationException('animated png rejected');
        }

        $limits = config('media.limits');
        if ($width > $limits['image_max_width'] || $height > $limits['image_max_height']) {
            throw new MediaValidationException('image dimensions exceed the maximum');
        }
        if ($width < $limits['image_min_width'] || $height < $limits['image_min_height']) {
            throw new MediaValidationException('image dimensions below the minimum');
        }

        return ['mime' => $mime, 'width' => $width, 'height' => $height];
    }

    private function isApng(string $localPath): bool
    {
        // APNG carries an 'acTL' chunk before the first 'IDAT'.
        $bytes = (string) file_get_contents($localPath, false, null, 0, 4096);
        $actl = strpos($bytes, 'acTL');
        $idat = strpos($bytes, 'IDAT');

        return $actl !== false && ($idat === false || $actl < $idat);
    }
}
