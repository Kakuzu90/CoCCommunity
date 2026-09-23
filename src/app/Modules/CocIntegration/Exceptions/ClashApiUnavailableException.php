<?php

declare(strict_types=1);

namespace App\Modules\CocIntegration\Exceptions;

class ClashApiUnavailableException extends ClashApiException
{
    public function __construct()
    {
        parent::__construct('Clash of Clans API is unavailable.');
    }
}
