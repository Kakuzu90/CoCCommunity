<?php

declare(strict_types=1);

namespace App\Modules\CocIntegration\Exceptions;

use InvalidArgumentException;

class InvalidTagException extends InvalidArgumentException
{
    public function __construct(public readonly string $rawTag)
    {
        parent::__construct("Invalid Clash of Clans player tag: {$rawTag}.");
    }
}
