<?php

use App\Domain\Auth\Enums\Ability;
use App\Domain\Auth\Enums\UserRole;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

/*
 * specs/11 §Broken authorization: a matrix test iterates every role × every admin ability and
 * asserts the permission matrix in specs/04 §2 exactly. The expected sets below are transcribed
 * straight from that table, independently of the Ability enum, so a wrong threshold in either the
 * enum or the gates fails here.
 */

/** @return array<string, list<string>> ability value => roles (by value) that hold it */
function matrixExpectations(): array
{
    return [
        // Moderator and above.
        Ability::AccessModeration->value => ['moderator', 'admin', 'super_admin'],
        Ability::ViewReportQueue->value => ['moderator', 'admin', 'super_admin'],
        Ability::ClaimReport->value => ['moderator', 'admin', 'super_admin'],
        Ability::HideContent->value => ['moderator', 'admin', 'super_admin'],
        Ability::WarnUser->value => ['moderator', 'admin', 'super_admin'],
        Ability::RestrictUser->value => ['moderator', 'admin', 'super_admin'],
        Ability::ReviewMediaQuarantine->value => ['moderator', 'admin', 'super_admin'],
        Ability::ViewModerationLog->value => ['moderator', 'admin', 'super_admin'],
        // Admin and above.
        Ability::AccessAdmin->value => ['admin', 'super_admin'],
        Ability::RemoveContent->value => ['admin', 'super_admin'],
        Ability::SuspendUser->value => ['admin', 'super_admin'],
        Ability::BanUser->value => ['admin', 'super_admin'],
        Ability::LiftSanction->value => ['admin', 'super_admin'],
        Ability::ResolveDisputes->value => ['admin', 'super_admin'],
        Ability::ForceOwnershipTransfer->value => ['admin', 'super_admin'],
        Ability::ApproveMarketplaceSeller->value => ['admin', 'super_admin'],
        Ability::ManageTags->value => ['admin', 'super_admin'],
        Ability::ViewAuditLog->value => ['admin', 'super_admin'],
        // Super admin only.
        Ability::ManageRoles->value => ['super_admin'],
        Ability::ManageSettings->value => ['super_admin'],
        Ability::HardDeleteUser->value => ['super_admin'],
        // Never granted, super admin included.
        Ability::Impersonate->value => [],
    ];
}

it('covers every defined ability in the matrix expectations', function () {
    $covered = array_keys(matrixExpectations());
    $defined = array_map(fn (Ability $a) => $a->value, Ability::cases());

    expect(array_diff($defined, $covered))->toBe([]);
});

dataset('roles', ['user', 'moderator', 'admin', 'super_admin']);

it('grants each role exactly the matrix abilities', function (string $roleValue) {
    $user = User::factory()->role(UserRole::from($roleValue))->create();

    foreach (matrixExpectations() as $ability => $allowedRoles) {
        $expected = in_array($roleValue, $allowedRoles, true);

        expect(Gate::forUser($user)->allows($ability))
            ->toBe($expected, "role {$roleValue} vs ability {$ability}");
    }
})->with('roles');

it('never grants impersonation, not even to a super admin', function () {
    foreach (['user', 'moderator', 'admin', 'super_admin'] as $roleValue) {
        $user = User::factory()->role(UserRole::from($roleValue))->create();

        expect(Gate::forUser($user)->denies(Ability::Impersonate->value))->toBeTrue();
    }
});
