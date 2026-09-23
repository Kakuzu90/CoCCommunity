<?php

declare(strict_types=1);

use App\Modules\CocIntegration\Exceptions\InvalidTagException;
use App\Modules\CocIntegration\Support\Tag;

test('it normalizes casing, spaces, missing hash and O to zero', function (): void {
    expect(Tag::normalize('2p0yqrl8v'))->toBe('#2P0YQRL8V')
        ->and(Tag::normalize('#2p0 yqrl8v'))->toBe('#2P0YQRL8V')
        ->and(Tag::normalize('OP0Y'))->toBe('#0P0Y'); // O folds to 0
});

test('it rejects tags with characters outside the tag alphabet', function (): void {
    expect(fn () => Tag::normalize('#ABC123'))->toThrow(InvalidTagException::class)
        ->and(Tag::isValid('#ABC123'))->toBeFalse()
        ->and(Tag::isValid('#2P0YQRL8V'))->toBeTrue();
});
