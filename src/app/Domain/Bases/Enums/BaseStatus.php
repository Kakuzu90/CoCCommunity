<?php

namespace App\Domain\Bases\Enums;

enum BaseStatus: string
{
    case Draft = 'draft';
    case Processing = 'processing';
    case Published = 'published';
    case Hidden = 'hidden';
    case Removed = 'removed';
}
