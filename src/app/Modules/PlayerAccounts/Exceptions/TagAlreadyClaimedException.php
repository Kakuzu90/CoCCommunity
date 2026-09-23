<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Exceptions;

use RuntimeException;

/**
 * Thrown when a user tries to link a tag another user already holds. They must
 * instead prove ownership with the in-game token (which transfers it) or open
 * a dispute — a tag is never silently re-attached.
 */
class TagAlreadyClaimedException extends RuntimeException
{
    public function __construct(public readonly string $tag)
    {
        parent::__construct("The tag {$tag} is already linked to another account.");
    }
}
