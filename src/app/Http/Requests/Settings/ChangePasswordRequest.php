<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-own-credentials') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $password = Password::min((int) config('accounts.password.min'));
        if (config('accounts.password.check_compromised')) {
            $password = $password->uncompromised();
        }

        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', $password],
        ];
    }
}
