<?php

namespace App\Domain\PlayerAccounts\Data;

use App\Domain\CocIntegration\Data\PlayerData;

/**
 * The "Is this you?" confirmation card shown before token entry (specs/13 §3 step 4). It carries the
 * public-facing player details plus, when the tag is already verified by someone else, that holder's
 * handle so the UI can explain the token-transfer path (specs/13 §4).
 */
final readonly class PlayerPreview
{
    public function __construct(
        public string $tag,
        public string $ign,
        public int $thLevel,
        public int $trophies,
        public ?string $clanName,
        public ?string $leagueName,
        public ?string $conflictHolder = null,
        public bool $stale = false,
    ) {}

    public static function fromPlayer(PlayerData $player, ?string $conflictHolder = null): self
    {
        return new self(
            tag: $player->tag,
            ign: $player->name,
            thLevel: $player->townHallLevel,
            trophies: $player->trophies,
            clanName: $player->clan?->name,
            leagueName: $player->league?->name,
            conflictHolder: $conflictHolder,
            stale: $player->stale,
        );
    }

    public function hasConflict(): bool
    {
        return $this->conflictHolder !== null;
    }
}
