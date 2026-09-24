<?php

namespace App\Support\Rules;

use App\Support\ValueObjects\ThLevel;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

final class ThLevelRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            new ThLevel($value);
        } catch (InvalidArgumentException) {
            $fail('The :attribute must be a valid Town Hall level.');
        }
    }
}
