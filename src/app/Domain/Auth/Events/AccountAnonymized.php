<?php

namespace App\Domain\Auth\Events;

final readonly class AccountAnonymized
{
    public function __construct(public int $userId) {}
}
