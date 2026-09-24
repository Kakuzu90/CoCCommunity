<?php

namespace App\Http\Requests\Auth;

use App\Domain\Auth\Data\RegistrationData;
use App\Support\Rules\DisposableEmailRule;
use App\Support\Rules\TurnstileRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guest();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $reserved = array_map('strtolower', (array) config('accounts.username.reserved'));

        $password = Password::min((int) config('accounts.password.min'));
        if (config('accounts.password.check_compromised')) {
            $password = $password->uncompromised(); // HIBP k-anonymity, fail-open (specs/04 §4)
        }

        return [
            'username' => [
                'required', 'string',
                'regex:'.config('accounts.username.pattern'),
                function (string $attribute, mixed $value, \Closure $fail) use ($reserved): void {
                    if (in_array(mb_strtolower((string) $value), $reserved, true)) {
                        $fail('That username is reserved. Please choose another.');
                    }
                },
                // Case-insensitive uniqueness across drivers. Username availability is public, so an
                // "already taken" message here is expected (only email existence is hidden).
                Rule::unique('users', 'username')->where(fn ($q) => $q->whereRaw('lower(username) = ?', [mb_strtolower((string) $this->input('username'))])),
            ],
            // No uniqueness rule on email by design: existence is signalled by email, not the form.
            'email' => ['required', 'string', 'email:rfc', 'max:254', new DisposableEmailRule],
            'password' => ['required', 'string', 'confirmed', $password],
            'cf-turnstile-response' => [new TurnstileRule($this->ip())],
            // Bot friction (specs/11): honeypot must stay empty, form must not be submitted instantly.
            config('accounts.registration.honeypot_field') => ['nullable', 'prohibited'],
            'form_started_at' => ['required', 'numeric'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $started = (int) $this->input('form_started_at');
            $minMs = (int) config('accounts.registration.min_fill_seconds') * 1000;
            if ($started > 0 && (now()->getTimestampMs() - $started) < $minMs) {
                $validator->errors()->add('form_started_at', 'That was too quick — please try again.');
            }
        });
    }

    public function toData(): RegistrationData
    {
        return new RegistrationData(
            username: (string) $this->validated('username'),
            email: (string) $this->validated('email'),
            password: (string) $this->validated('password'),
        );
    }
}
