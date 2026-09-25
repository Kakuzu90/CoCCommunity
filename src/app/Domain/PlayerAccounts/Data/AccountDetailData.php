<?php

namespace App\Domain\PlayerAccounts\Data;

use App\Domain\PlayerAccounts\Enums\CocAccountStatus;

final readonly class AccountDetailData
{
    /**
     * @param  array<string, array<int, array{name: string, level: int, maxLevel: int, slug: string}>>  $progression
     * @param  array<string, array{value: int, delta: ?int}>  $stats
     */
    public function __construct(
        public int $id,
        public string $ulid,
        public string $tag,
        public string $ign,
        public int $thLevel,
        public CocAccountStatus $status,
        public bool $featured,
        public ?string $clanTag,
        public ?string $clanRole,
        public ?int $leagueId,
        public ?string $leagueName,
        public ?string $syncedAtIso,
        public ?string $syncedAge,
        public bool $stale,
        public bool $owner,
        public int $imagesCount,
        public array $stats,
        public array $progression,
        public bool $clanShared = true,
    ) {}
}
