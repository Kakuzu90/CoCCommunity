<?php

declare(strict_types=1);

namespace App\Modules\Media\Exceptions;

use InvalidArgumentException;

class UnknownCollectionException extends InvalidArgumentException
{
    public function __construct(string $collection)
    {
        parent::__construct("Unknown media collection: {$collection}.");
    }
}
