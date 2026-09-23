<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Auth\Enums\Role;
use App\Modules\Auth\Services\RoleService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

test('unverified users cannot enter the dashboard', function (): void {
    $this->actingAs(User::factory()->unverified()->create())
        ->get('/dashboard')->assertRedirect(route('verification.notice'));
});

test('registration grants only the user role and sends verification mail', function (): void {
    Notification::fake();

    Volt::test('pages.auth.register')
        ->set('name', 'New Player')
        ->set('email', 'player@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')->assertHasNoErrors();

    $user = User::where('email', 'player@example.com')->firstOrFail();
    expect($user->getRoleNames()->all())->toBe([Role::User->value]);
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('role seeding is idempotent and does not promote users', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::User);
    app(RoleService::class)->seed();
    app(RoleService::class)->seed();

    $this->assertDatabaseCount('roles', 4);
    expect($user->fresh()->getRoleNames()->all())->toBe(['User']);
});

test('roles grant only their defined administrative permissions', function (Role $role): void {
    $user = User::factory()->create();
    $user->assignRole($role);

    foreach (['admin.access', 'audit.view-own', 'audit.view-all', 'roles.manage', 'settings.manage'] as $permission) {
        expect($user->can($permission))->toBe(in_array($permission, $role->permissions(), true));
    }
})->with(Role::cases());

test('all roles can manage their own account but cannot mutate another profile', function (Role $role): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $owner->assignRole($role);

    foreach (['update', 'delete'] as $ability) {
        expect(Gate::forUser($owner)->allows($ability, $owner))->toBeTrue();
        expect(Gate::forUser($owner)->allows($ability, $other))->toBeFalse();
    }
})->with(Role::cases());

test('role management cannot escalate above the actors rank', function (): void {
    $user = User::factory()->create();
    $moderator = User::factory()->create();
    $admin = User::factory()->create();
    $super = User::factory()->create();
    $user->assignRole(Role::User);
    $moderator->assignRole(Role::Moderator);
    $admin->assignRole(Role::Admin);
    $super->assignRole(Role::SuperAdmin);

    expect(Gate::forUser($admin)->allows('manageRole', [$user, Role::Moderator]))->toBeTrue();
    expect(Gate::forUser($admin)->allows('manageRole', [$user, Role::Admin]))->toBeFalse();
    expect(Gate::forUser($admin)->allows('manageRole', [$user, Role::SuperAdmin]))->toBeFalse();
    expect(Gate::forUser($admin)->allows('manageRole', [$super, Role::User]))->toBeFalse();
    expect(Gate::forUser($admin)->allows('manageRole', [$admin, Role::User]))->toBeFalse();
    expect(Gate::forUser($moderator)->allows('manageRole', [$user, Role::Moderator]))->toBeFalse();
    expect(Gate::forUser($user)->allows('manageRole', [$user, Role::SuperAdmin]))->toBeFalse();
    expect(Gate::forUser($super)->allows('manageRole', [$admin, Role::SuperAdmin]))->toBeTrue();

    $super->email_verified_at = null;
    expect(Gate::forUser($super)->allows('manageRole', [$user, Role::Admin]))->toBeFalse();
});

test('verification mail is rate limited through both resend forms', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);

    for ($i = 0; $i < 6; $i++) {
        Volt::test('pages.auth.verify-email')->call('sendVerification')->assertHasNoErrors();
    }

    Volt::test('profile.update-profile-information-form')->call('sendVerification')->assertStatus(429);
    Notification::assertSentToTimes($user, VerifyEmail::class, 6);
});

test('soft deleted users cannot sign in', function (): void {
    $user = User::factory()->create();
    $user->delete();

    Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password')
        ->call('login')->assertHasErrors('form.email');

    $this->assertGuest();
});
