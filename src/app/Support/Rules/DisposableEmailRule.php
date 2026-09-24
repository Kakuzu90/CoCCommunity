<?php

namespace App\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Rejects known disposable/throwaway email domains at registration (specs/11). The list is a
 * committed file ops refreshes monthly; it is cached so the file is not read on every request.
 */
final class DisposableEmailRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Str::contains($value, '@')) {
            return; // format is the `email` rule's job
        }

        $domain = Str::lower(Str::afterLast($value, '@'));
        if (in_array($domain, $this->domains(), true)) {
            $fail('This email provider is not allowed. Please use a permanent email address.');
        }
    }

    /** @return list<string> */
    private function domains(): array
    {
        return Cache::remember('accounts:disposable-domains', 3600, function (): array {
            $file = (string) config('accounts.disposable_domains_file');
            if (! is_file($file)) {
                return [];
            }

            $lines = preg_split('/\r?\n/', (string) file_get_contents($file)) ?: [];

            return array_values(array_filter(array_map(
                fn (string $l) => Str::lower(trim($l)),
                $lines,
            ), fn (string $l) => $l !== '' && ! str_starts_with($l, '#')));
        });
    }
}
