<?php

namespace App\Support\ValueObjects;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

final readonly class PlayerTag implements JsonSerializable, Stringable
{
    public string $value;

    public function __construct(mixed $value)
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException('A player tag must be a string.');
        }

        $tag = str_replace('O', '0', strtoupper(trim($value, " \t\r\n")));
        $tag = str_starts_with($tag, '#') ? substr($tag, 1) : $tag;
        $length = strlen($tag);

        if ($length < Config::integer('coc.tags.min_length')
            || $length > Config::integer('coc.tags.max_length')
            || strspn($tag, Config::string('coc.tags.alphabet')) !== $length) {
            throw new InvalidArgumentException('The player tag has an invalid length or character.');
        }

        $this->value = '#'.$tag;
    }

    public function encoded(): string
    {
        return rawurlencode($this->value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
