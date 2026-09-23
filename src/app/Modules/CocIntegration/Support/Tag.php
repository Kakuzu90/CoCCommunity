<?php

declare(strict_types=1);

namespace App\Modules\CocIntegration\Support;

use App\Modules\CocIntegration\Exceptions\InvalidTagException;

/**
 * Normalises and validates Clash of Clans player/clan tags.
 *
 * Supercell tags use a restricted alphabet (no O, I, 1, B, etc.); players
 * routinely type "O" for "0", so we fold that before validating.
 */
final class Tag
{
    private const ALPHABET = '0289PYLQGRJCUV';

    public static function normalize(string $raw): string
    {
        $tag = strtoupper(trim($raw));
        $tag = str_replace([' ', 'O'], ['', '0'], $tag);
        $tag = '#'.ltrim($tag, '#');

        if (preg_match('/^#['.self::ALPHABET.']{3,12}$/', $tag) !== 1) {
            throw new InvalidTagException($raw);
        }

        return $tag;
    }

    public static function isValid(string $raw): bool
    {
        try {
            self::normalize($raw);

            return true;
        } catch (InvalidTagException) {
            return false;
        }
    }
}
