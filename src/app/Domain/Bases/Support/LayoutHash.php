<?php

namespace App\Domain\Bases\Support;

use InvalidArgumentException;

final readonly class LayoutHash
{
    private function __construct(public string $value) {}

    public static function fromLayoutId(string $id): self
    {
        if (! preg_match('/^[A-Za-z0-9:_-]{8,512}$/D', $id)) {
            throw new InvalidArgumentException('The base link has an invalid layout ID.');
        }

        return new self(hash('sha256', $id));
    }
}
