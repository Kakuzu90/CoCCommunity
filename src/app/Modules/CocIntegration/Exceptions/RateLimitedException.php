<?php

declare(strict_types=1);

namespace App\Modules\CocIntegration\Exceptions;

class RateLimitedException extends ClashApiException
{
    public function __construct()
    {
        parent::__construct('Clash of Clans API rate limit reached.');
    }
}
