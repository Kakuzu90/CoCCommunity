<?php

namespace App\Domain\PlayerAccounts\Exceptions;

use App\Domain\PlayerAccounts\Enums\DisputeError;
use RuntimeException;

/** A recoverable dispute-workflow failure the UI turns into a precise message (specs/13 §5). */
final class DisputeException extends RuntimeException
{
    public function __construct(public readonly DisputeError $error)
    {
        parent::__construct($error->message());
    }

    public static function of(DisputeError $error): self
    {
        return new self($error);
    }
}
