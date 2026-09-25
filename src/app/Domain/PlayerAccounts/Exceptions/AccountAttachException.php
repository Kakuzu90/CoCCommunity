<?php

namespace App\Domain\PlayerAccounts\Exceptions;

use App\Domain\PlayerAccounts\Enums\AttachError;
use RuntimeException;

/**
 * A recoverable attach/verify failure the UI turns into a precise message (specs/13 §3). Distinct from
 * an invalid token, which is an ordinary result rather than an exception.
 */
final class AccountAttachException extends RuntimeException
{
    public function __construct(public readonly AttachError $error)
    {
        parent::__construct($error->message());
    }

    public static function of(AttachError $error): self
    {
        return new self($error);
    }
}
