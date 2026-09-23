<?php

declare(strict_types=1);

namespace App\Modules\CocIntegration\Exceptions;

class PlayerNotFoundException extends ClashApiException
{
    public function __construct(public readonly string $tag)
    {
        parent::__construct("Clash of Clans player not found for tag {$tag}.");
    }
}
