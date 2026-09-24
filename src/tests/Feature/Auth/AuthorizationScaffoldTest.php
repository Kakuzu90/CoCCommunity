<?php

use App\Domain\Auth\Enums\Ability;
use App\Domain\Auth\Enums\UserRole;
use App\Domain\Auth\Enums\UserStatus;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

/*
 * The role hierarchy, the super-admin `Gate::before`, and the UserPolicy structural rules
 * (specs/04 §1–§3). The exact role×ability matrix lives in tests/Security/PermissionMatrixTest.
 */

it('orders roles by hierarchy level', function () {
    expect(UserRole::User->level())->toBeLessThan(UserRole::Moderator->level())
        ->and(UserRole::Moderator->level())->toBeLessThan(UserRole::Admin->level())
        ->and(UserRole::Admin->level())->toBeLessThan(UserRole::SuperAdmin->level());

    expect(UserRole::Admin->atLeast(UserRole::Moderator))->toBeTrue()
        ->and(UserRole::Moderator->atLeast(UserRole::Admin))->toBeFalse()
        ->and(UserRole::Admin->atLeast(UserRole::Admin))->toBeTrue();
});

it('grants super admin every ability except impersonation via Gate::before', function () {
    $super = User::factory()->superAdmin()->create();

    foreach (Ability::cases() as $ability) {
        $allowed = Gate::forUser($super)->allows($ability->value);
        expect($allowed)->toBe($ability !== Ability::Impersonate, "super admin vs {$ability->value}");
    }
});

it('denies gate abilities to a staff account that cannot authenticate', function (string $status) {
    $admin = User::factory()->admin()->state(['status' => $status])->create();

    expect(Gate::forUser($admin)->allows(Ability::AccessAdmin->value))->toBeFalse();
})->with([
    UserStatus::Banned->value,
    UserStatus::Suspended->value,
]);

it('lets a moderator sanction a plain user but never a peer or superior', function () {
    $moderator = User::factory()->moderator()->create();
    $user = User::factory()->create();
    $otherMod = User::factory()->moderator()->create();
    $admin = User::factory()->admin()->create();

    expect($moderator->can('warn', $user))->toBeTrue()
        ->and($moderator->can('restrict', $user))->toBeTrue()
        ->and($moderator->can('warn', $otherMod))->toBeFalse()
        ->and($moderator->can('warn', $admin))->toBeFalse()
        ->and($moderator->can('warn', $moderator))->toBeFalse();
});

it('reserves suspend and ban for admins acting on lower roles', function () {
    $admin = User::factory()->admin()->create();
    $moderator = User::factory()->moderator()->create();
    $user = User::factory()->create();
    $otherAdmin = User::factory()->admin()->create();

    expect($moderator->can('suspend', $user))->toBeFalse()
        ->and($admin->can('suspend', $user))->toBeTrue()
        ->and($admin->can('ban', $moderator))->toBeTrue()
        ->and($admin->can('suspend', $otherAdmin))->toBeFalse();
});

it('reserves role changes and hard deletion for super admins', function () {
    $admin = User::factory()->admin()->create();
    $super = User::factory()->superAdmin()->create();
    $target = User::factory()->create();

    expect($admin->can('changeRole', $target))->toBeFalse()
        ->and($super->can('changeRole', $target))->toBeTrue()
        ->and($admin->can('hardDelete', $target))->toBeFalse()
        ->and($super->can('hardDelete', $target))->toBeTrue();
});

it('lets staff view the user list but not plain users', function () {
    $user = User::factory()->create();
    $moderator = User::factory()->moderator()->create();

    expect($user->can('viewAny', User::class))->toBeFalse()
        ->and($moderator->can('viewAny', User::class))->toBeTrue();
});
