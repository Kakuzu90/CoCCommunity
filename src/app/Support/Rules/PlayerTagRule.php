<?php

namespace App\Support\Rules;

use App\Support\ValueObjects\PlayerTag;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

final class PlayerTagRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            new PlayerTag($value);
        } catch (InvalidArgumentException) {
            $fail('The :attribute must be a valid player tag.');
        }
    }
}
