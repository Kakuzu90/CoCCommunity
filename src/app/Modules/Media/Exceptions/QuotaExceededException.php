<?php

declare(strict_types=1);

namespace App\Modules\Media\Exceptions;

use RuntimeException;

class QuotaExceededException extends RuntimeException
{
    public function __construct(public readonly string $collection, public readonly int $max)
    {
        parent::__construct("This collection allows at most {$max} file(s).");
    }
}
