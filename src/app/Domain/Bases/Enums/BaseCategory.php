<?php

namespace App\Domain\Bases\Enums;

enum BaseCategory: string
{
    case War = 'war';
    case Cwl = 'cwl';
    case Farming = 'farming';
    case Trophy = 'trophy';
    case Legend = 'legend';
    case AntiThreeStar = 'anti_3_star';
    case AntiTwoStar = 'anti_2_star';
    case Hybrid = 'hybrid';
    case Progress = 'progress';
    case Troll = 'troll';

    public function label(): string
    {
        return (string) config('bases.categories.'.$this->value);
    }
}
