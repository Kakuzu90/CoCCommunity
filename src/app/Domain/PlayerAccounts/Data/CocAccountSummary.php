<?php

namespace App\Domain\PlayerAccounts\Data;

use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Models\CocAccount;

/**
 * A row in the "my accounts" list (specs/13, 18 §6). A view DTO so Presentation never touches the model.
 */
final readonly class CocAccountSummary
{
    public function __construct(
        public int $id,
        public string $tag,
        public string $ign,
        public int $thLevel,
        public int $trophies,
        public CocAccountStatus $status,
        public bool $isFeatured,
        public ?string $clanTag,
        public ?string $leagueName,
        public ?string $verifiedAtIso,
    ) {}

    public static function fromModel(CocAccount $account): self
    {
        return new self(
            id: $account->id,
            tag: $account->tag,
            ign: $account->ign,
            thLevel: $account->th_level,
            trophies: $account->trophies,
            status: $account->status,
            isFeatured: $account->is_featured,
            clanTag: $account->clan_tag,
            leagueName: $account->league_name,
            verifiedAtIso: $account->verified_at?->toIso8601String(),
        );
    }
}
