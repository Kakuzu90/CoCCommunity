<?php

namespace App\Domain\Bases\Enums;

enum BaseVisibility: string
{
    case Public = 'public';
    case Unlisted = 'unlisted';
    case Private = 'private';
}
