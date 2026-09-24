<?php

use App\Support\ValueObjects\PlayerTag;

it('normalizes tags to a canonical identity', function (string $input, string $canonical) {
    $tag = new PlayerTag($input);

    expect($tag->value)->toBe($canonical)
        ->and((string) $tag)->toBe($canonical)
        ->and(json_encode($tag))->toBe(json_encode($canonical))
        ->and($tag->encoded())->toBe(rawurlencode($canonical))
        ->and($tag->equals(new PlayerTag($canonical)))->toBeTrue();
})->with([
    ['pyl', '#PYL'],
    ['#pyl', '#PYL'],
    [" \t#poy\r\n", '#P0Y'],
    ['o28', '#028'],
]);

it('honors the configured tag length boundaries', function () {
    $minimum = config('coc.tags.min_length');
    $maximum = config('coc.tags.max_length');
    expect((new PlayerTag(str_repeat('P', $minimum)))->value)->toBe('#'.str_repeat('P', $minimum));
    expect((new PlayerTag(str_repeat('P', $maximum)))->value)->toBe('#'.str_repeat('P', $maximum));
    expect(fn () => new PlayerTag(str_repeat('P', $minimum - 1)))->toThrow(InvalidArgumentException::class);
    expect(fn () => new PlayerTag(str_repeat('P', $maximum + 1)))->toThrow(InvalidArgumentException::class);
});

it('reads tag constraints from config', function () {
    config(['coc.tags.min_length' => 4, 'coc.tags.max_length' => 5, 'coc.tags.alphabet' => 'PQ']);
    expect((new PlayerTag('ppqq'))->value)->toBe('#PPQQ');
    foreach (['PPP', 'PPPPPP', 'PPPY'] as $tag) {
        expect(fn () => new PlayerTag($tag))->toThrow(InvalidArgumentException::class);
    }
});

it('keeps distinct tags distinct and prevents mutation', function () {
    $tag = new PlayerTag('PYL');
    expect($tag->equals(new PlayerTag('PYQ')))->toBeFalse();
    expect(function () use ($tag) {
        $tag->value = '#PYQ';
    })->toThrow(Error::class);
});
