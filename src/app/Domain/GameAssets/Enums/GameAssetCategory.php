<?php

namespace App\Domain\GameAssets\Enums;

/** The kinds of curated game asset the resolver serves. Clan badges are pass-through, not a case. */
enum GameAssetCategory: string
{
    case Unit = 'unit';
    case TownHall = 'townhall';
    case League = 'league';

    /** Sub-directory under game/{version}/ for this category (specs/10 §11). */
    public function directory(): string
    {
        return (string) config("assets.categories.{$this->value}", $this->value);
    }
}
