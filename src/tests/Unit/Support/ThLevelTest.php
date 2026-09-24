<?php

use App\Support\ValueObjects\ThLevel;

it('accepts positive integer levels including future levels', function (int|string $input, int $expected) {
    $level = new ThLevel($input);
    expect($level->value)->toBe($expected)
        ->and((string) $level)->toBe((string) $expected)
        ->and(json_encode($level))->toBe((string) $expected)
        ->and($level->equals(new ThLevel($expected)))->toBeTrue();
})->with([[1, 1], ['17', 17], [18, 18], [99, 99], [PHP_INT_MAX, PHP_INT_MAX], [(string) PHP_INT_MAX, PHP_INT_MAX]]);

it('honors the configured minimum with no game-level ceiling', function () {
    config(['coc.th_min_level' => 2]);
    expect(fn () => new ThLevel(1))->toThrow(InvalidArgumentException::class);
    expect((new ThLevel(2))->value)->toBe(2);
    expect((new ThLevel(99))->value)->toBe(99);
});

it('keeps distinct levels distinct and prevents mutation', function () {
    $level = new ThLevel(17);
    expect($level->equals(new ThLevel(18)))->toBeFalse();
    expect(function () use ($level) {
        $level->value = 18;
    })->toThrow(Error::class);
});
