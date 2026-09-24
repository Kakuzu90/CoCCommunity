<?php

namespace App\Domain\Users\Services;

use App\Domain\Users\Data\PrivacySettingsData;
use App\Domain\Users\Models\PrivacySetting;
use Illuminate\Contracts\Auth\Authenticatable;

class PrivacyService
{
    public function get(Authenticatable $user): PrivacySettingsData
    {
        return $this->toData($this->ensure((int) $user->getAuthIdentifier()));
    }

    public function update(Authenticatable $user, PrivacySettingsData $input): void
    {
        $this->ensure((int) $user->getAuthIdentifier())->fill([
            'profile_visibility' => $input->visibility,
            'show_coc_accounts' => $input->showCocAccounts,
            'show_clan' => $input->showClan,
            'show_activity' => $input->showActivity,
            'allow_recruitment_contact' => $input->allowRecruitmentContact,
            'allow_marketplace_contact' => $input->allowMarketplaceContact,
            'searchable' => $input->searchable,
        ])->save();
    }

    public function ensure(int $userId): PrivacySetting
    {
        $setting = PrivacySetting::query()->find($userId);
        if ($setting === null) {
            $setting = new PrivacySetting;
            $setting->user_id = $userId;
            $setting->save();
            $setting->refresh();
        }

        return $setting;
    }

    private function toData(PrivacySetting $setting): PrivacySettingsData
    {
        return new PrivacySettingsData(
            $setting->profile_visibility,
            $setting->show_coc_accounts,
            $setting->show_clan,
            $setting->show_activity,
            $setting->allow_recruitment_contact,
            $setting->allow_marketplace_contact,
            $setting->searchable,
        );
    }
}
