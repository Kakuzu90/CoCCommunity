<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmDeletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->routeIs('settings.deletion.cancel') ? 'cancel-own-deletion' : 'request-own-deletion') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['current_password' => ['required', 'current_password']];
    }
}
