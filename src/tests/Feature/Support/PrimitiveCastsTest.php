<?php

use App\Support\ValueObjects\PlayerTag;
use App\Support\ValueObjects\ThLevel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Models\PrimitiveRecord;

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::create('primitive_records', function (Blueprint $table) {
        $table->id();
        $table->string('tag')->nullable();
        $table->integer('th_level')->nullable();
    });
});

afterEach(function () {
    Schema::dropIfExists('primitive_records');
});

it('round trips typed values as scalars and serializes without leaking object structure', function () {
    $record = PrimitiveRecord::create(['tag' => new PlayerTag('poy'), 'th_level' => new ThLevel(99)]);
    $this->assertDatabaseHas('primitive_records', ['id' => $record->id, 'tag' => '#P0Y', 'th_level' => 99]);
    $record->refresh();
    expect($record->tag)->toBeInstanceOf(PlayerTag::class)
        ->and($record->tag->value)->toBe('#P0Y')
        ->and($record->th_level)->toBeInstanceOf(ThLevel::class)
        ->and($record->th_level->value)->toBe(99)
        ->and($record->toArray())->toMatchArray(['tag' => '#P0Y', 'th_level' => 99])
        ->and(json_decode($record->toJson(), true))->toMatchArray(['tag' => '#P0Y', 'th_level' => 99]);

    $record->tag = 'pyl';
    $record->th_level = '18';
    $record->save();
    $record->refresh();
    expect($record->tag->value)->toBe('#PYL')->and($record->th_level->value)->toBe(18);
});

it('preserves nullable attributes including replacement of cached objects', function () {
    $record = PrimitiveRecord::create(['tag' => 'pyl', 'th_level' => 17]);
    expect($record->tag)->toBeInstanceOf(PlayerTag::class);
    $record->update(['tag' => null, 'th_level' => null]);
    $record->refresh();
    expect($record->tag)->toBeNull()->and($record->th_level)->toBeNull()
        ->and($record->toArray())->toMatchArray(['tag' => null, 'th_level' => null]);
    $this->assertDatabaseHas('primitive_records', ['id' => $record->id, 'tag' => null, 'th_level' => null]);
});

it('rejects invalid direct model assignments before persistence', function (string $attribute, mixed $value) {
    expect(fn () => PrimitiveRecord::create([$attribute => $value]))->toThrow(InvalidArgumentException::class);
    $this->assertDatabaseCount('primitive_records', 0);
})->with([
    ['tag', 'invalid'], ['tag', 289], ['tag', ['PYL']], ['tag', new stdClass],
    ['th_level', 0], ['th_level', 17.5], ['th_level', true], ['th_level', '1e2'],
]);

it('rejects corrupt stored values on read', function (string $attribute, mixed $value) {
    $id = DB::table('primitive_records')->insertGetId([$attribute => $value]);
    $record = PrimitiveRecord::findOrFail($id);
    expect(fn () => $record->getAttribute($attribute))->toThrow(InvalidArgumentException::class);
})->with([['tag', 'invalid'], ['th_level', -1]]);
