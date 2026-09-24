<?php

namespace App\Domain\Media\Storage;

use App\Domain\Media\Contracts\ImageProcessor;
use App\Domain\Media\Data\ProcessedImage;
use App\Domain\Media\Data\Rendition;
use App\Domain\Media\Enums\MediaCollection;
use App\Domain\Media\Enums\MediaVariant;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;

/**
 * Re-encodes to WebP with Intervention v3 on GD. Re-encoding is the primary malware control and
 * drops all EXIF/metadata (GPS removal) by construction (specs/10 §5). Images are never upscaled.
 */
class InterventionImageProcessor implements ImageProcessor
{
    public function process(string $originalPath, MediaCollection $collection, string $outputDir): ProcessedImage
    {
        $config = $collection->config();
        if ($config === null) {
            throw new InvalidArgumentException("Collection {$collection->value} is not configured.");
        }

        $manager = new ImageManager(new Driver);
        $quality = (int) config('media.webp_quality');

        $probe = $manager->read($originalPath);
        $originalWidth = $probe->width();
        $originalHeight = $probe->height();

        $renditions = [];
        /** @var array<string, array<string, mixed>> $variants */
        $variants = $config['variants'];
        foreach ($variants as $name => $spec) {
            $image = $manager->read($originalPath);
            $width = (int) $spec['width'];

            if (($spec['crop'] ?? false) === true) {
                // Square, centre-cropped (avatars): fill the box exactly.
                $image->cover($width, (int) $spec['height']);
            } else {
                // Preserve aspect ratio, never upscale a smaller original.
                $image->scaleDown(width: $width);
            }

            $path = $outputDir.'/'.$name.'.webp';
            $encoded = $image->toWebp($quality);
            $encoded->save($path);

            $renditions[] = new Rendition(
                variant: MediaVariant::from((string) $name),
                localPath: $path,
                width: $image->width(),
                height: $image->height(),
                sizeBytes: strlen((string) $encoded),
                mime: 'image/webp',
            );
        }

        return new ProcessedImage($originalWidth, $originalHeight, $renditions);
    }
}
