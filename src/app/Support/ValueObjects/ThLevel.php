<?php

namespace App\Support\ValueObjects;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

final readonly class ThLevel implements JsonSerializable, Stringable
{
    public int $value;

    public function __construct(mixed $value)
    {
        // HTTP forms and database drivers may supply decimal strings. Do not coerce floats,
        // booleans, exponents, signed strings, or values that overflow a PHP integer.
        if (! is_int($value) && (! is_string($value) || preg_match('/\A[1-9][0-9]*\z/', $value) !== 1)) {
            throw new InvalidArgumentException('A Town Hall level must be a positive integer.');
        }

        $level = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => Config::integer('coc.th_min_level')],
        ]);

        if ($level === false) {
            throw new InvalidArgumentException('The Town Hall level is outside the supported integer range.');
        }

        $this->value = $level;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
