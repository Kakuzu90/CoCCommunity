<?php

declare(strict_types=1);

namespace App\Modules\Media\Support;

/**
 * Detects the real content type of a file from its bytes — never trusts the
 * extension or the client-supplied mime. Images are checked with GD (robust
 * magic-byte parsing); other types fall back to fileinfo.
 */
final class FileSignature
{
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov',
    ];

    public static function detect(string $bytes): ?string
    {
        $image = @getimagesizefromstring($bytes);
        if ($image !== false && ! empty($image['mime'])) {
            return $image['mime'];
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_buffer($finfo, $bytes) ?: null;
            finfo_close($finfo);

            return $mime;
        }

        return null;
    }

    public static function extensionFor(string $mime): string
    {
        return self::EXTENSIONS[$mime] ?? 'bin';
    }
}
