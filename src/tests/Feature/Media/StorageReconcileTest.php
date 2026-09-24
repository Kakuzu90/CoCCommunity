<?php

use App\Domain\Media\Models\Media;
use App\Domain\Media\Models\MediaVariant;
use App\Domain\Media\Services\StorageReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('r2');
    config(['media.disk' => 'r2', 'assets.prefix' => 'game']);
});

it('reports orphaned public objects but never lists the game/ prefix', function () {
    $media = Media::factory()->ready()->create(['path' => 'public/avatar/01/full.webp', 'expires_at' => now()->addDay()]);
    Storage::disk('r2')->put('public/avatar/01/full.webp', 'known');
    Storage::disk('r2')->put('public/avatar/99/orphan.webp', 'orphan');
    // A game asset has no media row by design — an unguarded reconcile would delete the whole pack.
    Storage::disk('r2')->put('game/1/units/barbarian.png', 'game art');

    $report = app(StorageReconciler::class)->reconcile();

    expect($report->orphanObjects)->toContain('public/avatar/99/orphan.webp')
        ->and($report->orphanObjects)->not->toContain('public/avatar/01/full.webp')
        ->and($report->orphanObjects)->not->toContain('game/1/units/barbarian.png')
        ->and($report->scannedPrefixes)->not->toContain('game');

    expect($media->path)->toBe('public/avatar/01/full.webp');
});

it('reports a database row whose object is missing', function () {
    Media::factory()->ready()->create(['path' => 'public/avatar/02/full.webp', 'expires_at' => now()->addDay()]);
    MediaVariant::query(); // ensure model is loaded

    $report = app(StorageReconciler::class)->reconcile();

    expect($report->missingObjects)->toContain('public/avatar/02/full.webp');
});

it('excludes the game asset prefix from the reconcile allowlist by construction', function () {
    expect(StorageReconciler::PREFIXES)->not->toContain(config('assets.prefix'))
        ->and(StorageReconciler::PREFIXES)->toBe(['quarantine', 'public', 'private']);
});
