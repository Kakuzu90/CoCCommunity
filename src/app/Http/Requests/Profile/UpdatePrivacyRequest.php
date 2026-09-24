<?php

namespace App\Http\Requests\Profile;

use App\Domain\Users\Data\PrivacySettingsData;
use App\Domain\Users\Enums\ProfileVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePrivacyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update-privacy') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'profile_visibility' => ['required', Rule::enum(ProfileVisibility::class)],
            'show_coc_accounts' => ['required', 'boolean'],
            'show_clan' => ['required', 'boolean'],
            'show_activity' => ['required', 'boolean'],
            'allow_recruitment_contact' => ['required', 'boolean'],
            'allow_marketplace_contact' => ['required', 'boolean'],
            'searchable' => ['required', 'boolean'],
        ];
    }

    public function toData(): PrivacySettingsData
    {
        return new PrivacySettingsData(
            ProfileVisibility::from($this->validated('profile_visibility')),
            $this->boolean('show_coc_accounts'),
            $this->boolean('show_clan'),
            $this->boolean('show_activity'),
            $this->boolean('allow_recruitment_contact'),
            $this->boolean('allow_marketplace_contact'),
            $this->boolean('searchable'),
        );
    }
}
