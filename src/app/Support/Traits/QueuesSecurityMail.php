<?php

namespace App\Support\Traits;

use Illuminate\Bus\Queueable;

trait QueuesSecurityMail
{
    use Queueable;

    /** @return array<string, string> */
    public function viaQueues(): array
    {
        return ['mail' => 'low'];
    }
}
