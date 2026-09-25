<?php

namespace App\Domain\PlayerAccounts\Data;

/**
 * The accounts block of a public profile (FR-PROFILE-5, specs/18 §6). `featured` is pulled out of the list so
 * the page can give it the hero slot. The verified summary (count, war stars, highest Town Hall) covers
 * verified accounts only, so a tag under review does not inflate it.
 */
final readonly class ProfileAccounts
{
    /** @param  list<AccountDetailData>  $others */
    public function __construct(
        public ?AccountDetailData $featured,
        public array $others,
        public int $warStars,
        public int $verifiedCount = 0,
        public ?int $highestThLevel = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->featured === null && $this->others === [];
    }

    public static function empty(): self
    {
        return new self(null, [], 0);
    }
}
