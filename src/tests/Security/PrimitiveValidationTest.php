<?php

use App\Support\Rules\PlayerTagRule;
use App\Support\Rules\ThLevelRule;
use App\Support\ValueObjects\PlayerTag;
use App\Support\ValueObjects\ThLevel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    Http::preventStrayRequests();
});

it('rejects invalid tags at construction and validation without network calls', function (mixed $input) {
    expect(fn () => new PlayerTag($input))->toThrow(InvalidArgumentException::class);
    $validator = Validator::make(['tag' => $input], ['tag' => ['required', new PlayerTagRule]]);
    expect($validator->fails())->toBeTrue()->and($validator->errors()->has('tag'))->toBeTrue();
    Http::assertNothingSent();
})->with([
    'empty' => [''], 'null' => [null], 'array' => [[]], 'integer' => [289],
    'boolean' => [true], 'float' => [289.0], 'object' => [new stdClass],
    'short' => ['PY'], 'long' => ['PPPPPPPPPPPPP'], 'double hash' => ['##PYL'],
    'bad alphabet' => ['ABC'], 'internal whitespace' => ['P YL'],
    'unicode lookalike' => ['ＰYL'], 'encoded hash' => ['%23PYL'],
    'NUL' => ["#PYL\0"], 'embedded newline' => ["#P\nYL"],
    'URL path injection' => ['#PYL/../clans'], 'query injection' => ['#PYL?token=x'],
    'XSS' => ['<script>alert(1)</script>'], 'SQL' => ["#PYL' OR 1=1--"],
]);

it('rejects invalid levels without lossy coercion', function (mixed $input) {
    expect(fn () => new ThLevel($input))->toThrow(InvalidArgumentException::class);
    $validator = Validator::make(['th' => $input], ['th' => ['required', new ThLevelRule]]);
    expect($validator->fails())->toBeTrue()->and($validator->errors()->has('th'))->toBeTrue();
})->with([
    'zero' => [0], 'negative' => [-1], 'zero string' => ['0'], 'empty' => [''],
    'null' => [null], 'array' => [[]], 'bool' => [true], 'object' => [new stdClass],
    'float' => [17.5], 'integer float' => [17.0], 'float string' => ['17.0'],
    'exponent' => ['1e2'], 'whitespace' => [' 17 '], 'leading zero' => ['017'],
    'signed' => ['+17'], 'suffix' => ['17foo'], 'overflow' => [(string) PHP_INT_MAX.'0'],
]);

it('uses the same normalization rules as the value objects', function () {
    $validator = Validator::make(['tag' => ' #poy ', 'th' => '99'], [
        'tag' => ['required', new PlayerTagRule], 'th' => ['required', new ThLevelRule],
    ]);
    expect($validator->passes())->toBeTrue();
    // Rules validate only. Construct values from validated data before passing to a service.
    expect((new PlayerTag($validator->validated()['tag']))->value)->toBe('#P0Y');
    expect((new ThLevel($validator->validated()['th']))->value)->toBe(99);
    Http::assertNothingSent();
});
