<?php

namespace App\Domain\GameAssets\Contracts;

use App\Domain\GameAssets\Data\GameAsset;

/**
 * The single seam through which every game asset is referenced (specs/18 §2.3). No template ever
 * hardcodes a `game/` path: turning the category off, swapping to originals or moving the delivery
 * origin is then one class. Two sources sit behind it, invisible to callers — a versioned manifest
 * for the curated catalogue, and pass-through of the API's own URL for clan badges (specs/10 §11.1).
 */
interface GameAssetResolver
{
    public function unit(string $slug, ?string $name = null): GameAsset;

    public function townHall(int $level): GameAsset;

    public function league(int $leagueId, ?string $name = null): GameAsset;

    /** Clan badges are referenced from the API's `badgeUrls`, never mirrored (specs/10 §11.1). */
    public function clanBadge(?string $badgeUrl, string $clanName): GameAsset;

    /** The kill switch (`config('assets.enabled')`) — false makes every call return a placeholder. */
    public function enabled(): bool;
}
