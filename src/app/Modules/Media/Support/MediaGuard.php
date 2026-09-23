<?php

declare(strict_types=1);

namespace App\Modules\Media\Support;

use App\Modules\Media\Exceptions\MediaValidationException;
use App\Modules\Media\Exceptions\UnknownCollectionException;

/**
 * Server-side validation for uploads: collection rules, size, and real content
 * type (magic bytes) — the extension and client mime are never trusted.
 */
final class MediaGuard
{
    /** @return array<string, mixed> */
    public static function collection(string $collection): array
    {
        $config = config("media.collections.{$collection}");

        if (! is_array($config)) {
            throw new UnknownCollectionException($collection);
        }

        return $config;
    }

    /**
     * Validate raw bytes against a collection and return the detected mime.
     *
     * @param  array<string, mixed>  $config
     */
    public static function validate(string $bytes, int $size, array $config): string
    {
        if ($size > (int) $config['max_size']) {
            throw MediaValidationException::tooLarge((int) $config['max_size']);
        }

        $mime = FileSignature::detect($bytes);

        if ($mime === null) {
            throw MediaValidationException::unreadable();
        }

        if (! in_array($mime, $config['mimes'], true)) {
            throw MediaValidationException::unsupportedType($mime);
        }

        return $mime;
    }
}
