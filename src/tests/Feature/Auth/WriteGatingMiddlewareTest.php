<?php

use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/*
 * The three write-gating middleware and the role floor (specs/04 §3). Throwaway routes exercise the
 * aliases registered in bootstrap/app.php; the features that own each write surface apply them later.
 */

beforeEach(function () {
    Route::middleware(['web', 'active'])->get('/t/active', fn () => 'ok');
    Route::middleware(['web', 'verified'])->get('/t/verified', fn () => 'ok');
    Route::middleware(['web', 'coc.verified'])->get('/t/coc', fn () => 'ok');
    Route::middleware(['web', 'role:moderator'])->get('/t/mod', fn () => 'ok');
    Route::middleware(['web', 'role:admin'])->get('/t/admin', fn () => 'ok');
});

it('sends a guest to login instead of through a write gate', function () {
    $this->get('/t/active')->assertRedirect(route('login'));
});

it('lets an active account write and blocks every other status', function () {
    $this->actingAs(User::factory()->create())->get('/t/active')->assertOk();

    $this->actingAs(User::factory()->restricted()->create())->get('/t/active')->assertForbidden();
    $this->actingAs(User::factory()->suspended()->create())->get('/t/active')->assertForbidden();
    $this->actingAs(User::factory()->banned()->create())->get('/t/active')->assertForbidden();
    $this->actingAs(User::factory()->pendingDeletion()->create())->get('/t/active')->assertForbidden();
});

it('redirects an unverified account to the verification notice', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get('/t/verified')->assertRedirect(route('verification.notice'));

    $this->actingAs(User::factory()->create())->get('/t/verified')->assertOk();
});

it('requires a verified in-game account for the coc gate', function () {
    $this->actingAs(User::factory()->create())->get('/t/coc')->assertForbidden();

    $this->actingAs(User::factory()->withVerifiedCocAccount()->create())->get('/t/coc')->assertOk();
});

it('enforces the role floor', function () {
    $this->actingAs(User::factory()->create())->get('/t/admin')->assertForbidden();
    $this->actingAs(User::factory()->moderator()->create())->get('/t/admin')->assertForbidden();
    $this->actingAs(User::factory()->moderator()->create())->get('/t/mod')->assertOk();
    $this->actingAs(User::factory()->admin()->create())->get('/t/admin')->assertOk();
    $this->actingAs(User::factory()->superAdmin()->create())->get('/t/mod')->assertOk();
});
