<?php

namespace App\Domain\Media\Contracts;

use App\Domain\Media\Data\ProcessedImage;
use App\Domain\Media\Enums\MediaCollection;

/**
 * Re-encodes a validated original into the collection's WebP variants, stripping EXIF/metadata.
 * Isolated behind an interface so the processing library is swappable (specs/10 §5).
 */
interface ImageProcessor
{
    public function process(string $originalPath, MediaCollection $collection, string $outputDir): ProcessedImage;
}
