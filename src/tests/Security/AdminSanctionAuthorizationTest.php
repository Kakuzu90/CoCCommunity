<?php

use App\Domain\Auth\Enums\UserStatus;
use App\Domain\Auth\Models\User;
use App\Domain\Moderation\Enums\ReasonCode;
use App\Domain\Moderation\Enums\SanctionType;
use App\Domain\Moderation\Services\SanctionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Authorization is enforced at the service layer, not merely by route middleware or a hidden button
 * (specs/04 §3). These call the SanctionService directly, bypassing the controller's role gate, to
 * prove the policy still decides — and that a denied action writes nothing (transaction rollback).
 */
it('denies a moderator suspending a user, even calling the service directly', function () {
    $moderator = User::factory()->moderator()->create();
    $target = User::factory()->create();

    expect(fn () => app(SanctionService::class)->apply(
        $moderator->id, $target->id, SanctionType::Suspension, ReasonCode::Spam, 'x', null, 1,
    ))->toThrow(AuthorizationException::class);

    expect($target->fresh()->status)->toBe(UserStatus::Active);
    $this->assertDatabaseCount('user_sanctions', 0);
    $this->assertDatabaseCount('moderation_actions', 0);
    $this->assertDatabaseCount('audit_logs', 0);
});

it('denies an admin banning a super admin (never act on a superior)', function () {
    $admin = User::factory()->admin()->create();
    $superAdmin = User::factory()->superAdmin()->create();

    expect(fn () => app(SanctionService::class)->apply(
        $admin->id, $superAdmin->id, SanctionType::Ban, ReasonCode::Hate, 'x', null, null,
    ))->toThrow(AuthorizationException::class);

    expect($superAdmin->fresh()->status)->toBe(UserStatus::Active);
    $this->assertDatabaseCount('audit_logs', 0);
});

it('denies a plain user any sanction power', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    expect(fn () => app(SanctionService::class)->apply(
        $user->id, $target->id, SanctionType::Warning, ReasonCode::Spam, 'x', null, null,
    ))->toThrow(AuthorizationException::class);
});

it('lets a moderator warn a plain user but not the reverse escalation', function () {
    $moderator = User::factory()->moderator()->create();
    $target = User::factory()->create();

    $result = app(SanctionService::class)->apply(
        $moderator->id, $target->id, SanctionType::Warning, ReasonCode::Spam, 'Cool it.', null, null,
    );

    expect($result->afterStatus)->toBe(UserStatus::Active);
    $this->assertDatabaseHas('audit_logs', ['action' => 'user.warned', 'actor_id' => $moderator->id]);
});
