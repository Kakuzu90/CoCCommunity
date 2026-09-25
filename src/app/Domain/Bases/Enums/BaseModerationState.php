<?php

namespace App\Domain\Bases\Enums;

enum BaseModerationState: string
{
    case Clean = 'clean';
    case Flagged = 'flagged';
    case UnderReview = 'under_review';
    case Actioned = 'actioned';
}
