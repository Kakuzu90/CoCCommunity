<?php

namespace App\Domain\Users\Data;

use App\Domain\Users\Enums\ProfileVisibility;

final readonly class PrivacySettingsData
{
    public function __construct(
        public ProfileVisibility $visibility,
        public bool $showCocAccounts,
        public bool $showClan,
        public bool $showActivity,
        public bool $allowRecruitmentContact,
        public bool $allowMarketplaceContact,
        public bool $searchable,
    ) {}
}
