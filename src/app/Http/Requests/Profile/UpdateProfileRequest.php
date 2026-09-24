<?php

namespace App\Http\Requests\Profile;

use App\Domain\Users\Data\ProfileInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Validates and shapes profile-edit input (specs/07 `profiles`, specs/11 mass-assignment). Only the
 * editable presentation fields are accepted; role, status, counters and avatar are set elsewhere.
 * Returns a typed ProfileInput so the service never sees a request array.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Normalise before validation: trim and drop blank language tags (the tag input can submit an
     * empty trailing field) and keep only known social platforms so unknown keys cannot slip
     * through the array rule.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        $languages = $this->input('languages');
        if (is_array($languages)) {
            $merge['languages'] = array_values(array_filter(
                array_map(fn ($l) => is_string($l) ? trim($l) : $l, $languages),
                fn ($l) => is_string($l) && $l !== '',
            ));
        }

        $socials = $this->input('socials');
        if (is_array($socials)) {
            $allowed = array_flip((array) config('accounts.profile.socials'));
            $merge['socials'] = array_intersect_key($socials, $allowed);
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $p = config('accounts.profile');

        $rules = [
            'display_name' => ['nullable', 'string', 'max:'.$p['display_name_max']],
            'bio' => ['nullable', 'string', 'max:'.$p['bio_max']],
            'country_code' => ['nullable', 'string', 'regex:'.$p['country_pattern']],
            'languages' => ['nullable', 'array', 'max:'.$p['languages_max']],
            'languages.*' => ['string', 'max:'.$p['language_max'], 'distinct:ignore_case'],
            'timezone' => ['nullable', 'timezone'],
            'socials' => ['nullable', 'array'],
        ];

        foreach ((array) $p['socials'] as $platform) {
            // No spaces/newlines: a handle or URL, length-capped. Stored raw, escaped on render.
            $rules["socials.{$platform}"] = ['nullable', 'string', 'max:'.$p['social_max'], 'regex:/^\S+$/'];
        }

        return $rules;
    }

    public function toInput(): ProfileInput
    {
        $languages = array_values((array) $this->validated('languages', []));

        $socials = array_filter(
            (array) $this->validated('socials', []),
            fn ($v): bool => is_string($v) && $v !== '',
        );

        $country = $this->validated('country_code');

        return new ProfileInput(
            displayName: $this->nullableString('display_name'),
            bio: $this->nullableString('bio'),
            countryCode: $country !== null && $country !== '' ? Str::upper($country) : null,
            languages: $languages,
            timezone: $this->nullableString('timezone'),
            socials: $socials,
        );
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->validated($key);
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
