<?php

declare(strict_types=1);

namespace App\Modules\Media\Enums;

enum MediaKind: string
{
    case Image = 'image';
    case Video = 'video';
}
