<?php

declare(strict_types=1);

namespace App\Modules\Media\Enums;

enum MediaStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Rejected = 'rejected';
}
