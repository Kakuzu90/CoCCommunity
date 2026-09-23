<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Enums;

enum ReportStatus: string
{
    case Open = 'open';
    case Assigned = 'assigned';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
}
