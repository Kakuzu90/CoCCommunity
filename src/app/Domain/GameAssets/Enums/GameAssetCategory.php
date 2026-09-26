<?php

namespace App\Domain\GameAssets\Enums;

/** The kinds of curated game asset the resolver serves. Clan badges are pass-through, not a case. */
enum GameAssetCategory: string
{
    case Unit = 'unit';
    case TownHall = 'townhall';
    case League = 'league';
}
