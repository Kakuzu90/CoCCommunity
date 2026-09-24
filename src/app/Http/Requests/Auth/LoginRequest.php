<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guest();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }
}
