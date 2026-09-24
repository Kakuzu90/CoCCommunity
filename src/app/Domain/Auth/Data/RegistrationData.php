<?php

namespace App\Domain\Auth\Data;

/** Validated registration input handed from the form request to the service (specs/11: typed DTOs). */
final readonly class RegistrationData
{
    public function __construct(
        public string $username,
        public string $email,
        public string $password,
    ) {}
}
