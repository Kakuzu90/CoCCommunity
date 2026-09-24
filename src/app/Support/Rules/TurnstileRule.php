<?php

namespace App\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifies a Cloudflare Turnstile token server-side (specs/11). When Turnstile is disabled
 * (`services.turnstile.enabled=false`, e.g. tests) the rule passes. A transport failure fails
 * closed for a human-solvable challenge but is logged, so an outage is visible.
 */
final class TurnstileRule implements ValidationRule
{
    public function __construct(private readonly ?string $ip = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('services.turnstile.enabled')) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Please complete the verification challenge.');

            return;
        }

        try {
            $response = Http::asForm()->timeout(5)->post((string) config('services.turnstile.verify_url'), [
                'secret' => (string) config('services.turnstile.secret'),
                'response' => $value,
                'remoteip' => $this->ip,
            ]);
            $ok = $response->successful() && $response->json('success') === true;
        } catch (\Throwable $e) {
            Log::warning('turnstile.unreachable', ['message' => $e->getMessage()]);
            $ok = false;
        }

        if (! $ok) {
            $fail('Verification failed. Please try again.');
        }
    }
}
