<?php

namespace App\Domain\Media\Enums;

enum MediaVisibility: string
{
    case Public = 'public';
    case Private = 'private';
}
