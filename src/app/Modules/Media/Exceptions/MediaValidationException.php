<?php

declare(strict_types=1);

namespace App\Modules\Media\Exceptions;

use RuntimeException;

class MediaValidationException extends RuntimeException
{
    public static function tooLarge(int $max): self
    {
        return new self("File exceeds the maximum size of {$max} bytes.");
    }

    public static function unsupportedType(string $mime): self
    {
        return new self("Unsupported or mismatched file type: {$mime}.");
    }

    public static function unreadable(): self
    {
        return new self('The file could not be read or is not a recognised media type.');
    }
}
