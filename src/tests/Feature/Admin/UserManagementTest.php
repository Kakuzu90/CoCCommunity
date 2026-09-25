<?php

use App\Domain\Auth\Enums\UserStatus;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests from the admin area to login', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

it('forbids plain users and moderators from the admin area', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs(User::factory()->moderator()->create())->get(route('admin.users.index'))->assertForbidden();
});

it('lets an admin browse, search and filter the user list', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['username' => 'sparrowhawk']);
    User::factory()->suspended()->create(['username' => 'gollum']);

    $this->actingAs($admin)->get(route('admin.users.index'))
        ->assertOk()->assertSee('sparrowhawk')->assertSee('gollum');

    $this->actingAs($admin)->get(route('admin.users.index', ['q' => 'sparrow']))
        ->assertOk()->assertSee('sparrowhawk')->assertDontSee('gollum');

    $this->actingAs($admin)->get(route('admin.users.index', ['status' => UserStatus::Suspended->value]))
        ->assertOk()->assertSee('gollum')->assertDontSee('sparrowhawk');
});

it('excludes the viewing admin from the user list', function () {
    $admin = User::factory()->admin()->create(['username' => 'selfadmin', 'email' => 'self@example.test']);
    User::factory()->create(['username' => 'someoneelse']);

    // The email only appears in the table, not the header account chip, so its absence proves the
    // viewing admin is excluded from the list itself.
    $this->actingAs($admin)->get(route('admin.users.index'))
        ->assertOk()->assertSee('someoneelse')->assertDontSee('self@example.test');
});

it('shows a user detail page and 404s an unknown handle', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create(['username' => 'frodo']);

    $this->actingAs($admin)->get(route('admin.users.show', 'frodo'))->assertOk()->assertSee('frodo');
    $this->actingAs($admin)->get(route('admin.users.show', 'nobody'))->assertNotFound();
});

it('suspends a user and writes the sanction, moderation action and audit rows', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create(['username' => 'saruman']);

    $this->actingAs($admin)->post(route('admin.users.sanctions.store', 'saruman'), [
        'type' => 'suspension',
        'reason_code' => 'harassment',
        'public_reason' => 'Repeated harassment in comments.',
        'internal_note' => 'Third report this week.',
        'duration_days' => 7,
    ])->assertRedirect(route('admin.users.show', 'saruman'));

    $target->refresh();
    expect($target->status)->toBe(UserStatus::Suspended)
        ->and($target->status_reason)->toBe('Repeated harassment in comments.')
        ->and($target->status_expires_at)->not->toBeNull();

    $this->assertDatabaseHas('user_sanctions', [
        'user_id' => $target->id, 'type' => 'suspension', 'reason_code' => 'harassment', 'issued_by' => $admin->id,
    ]);
    $this->assertDatabaseHas('moderation_actions', [
        'actor_id' => $admin->id, 'action' => 'suspend', 'target_user_id' => $target->id, 'reason_code' => 'harassment',
    ]);
    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id, 'action' => 'user.suspended', 'auditable_type' => 'user', 'auditable_id' => $target->id,
    ]);
});

it('bans a user permanently with no expiry', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create(['username' => 'smeagol']);

    $this->actingAs($admin)->post(route('admin.users.sanctions.store', 'smeagol'), [
        'type' => 'ban',
        'reason_code' => 'account_trading',
        'public_reason' => 'Selling accounts.',
    ])->assertRedirect();

    $target->refresh();
    expect($target->status)->toBe(UserStatus::Banned)->and($target->status_expires_at)->toBeNull();
});

it('records a warning without changing account status', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create(['username' => 'pippin']);

    $this->actingAs($admin)->post(route('admin.users.sanctions.store', 'pippin'), [
        'type' => 'warning',
        'reason_code' => 'spam',
        'public_reason' => 'Please stop cross-posting.',
    ])->assertRedirect();

    $target->refresh();
    expect($target->status)->toBe(UserStatus::Active);
    $this->assertDatabaseHas('user_sanctions', ['user_id' => $target->id, 'type' => 'warning']);
});

it('requires a public reason and a duration for time-boxed sanctions', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create(['username' => 'bilbo']);

    $this->actingAs($admin)->post(route('admin.users.sanctions.store', 'bilbo'), [
        'type' => 'suspension', 'reason_code' => 'spam',
    ])->assertSessionHasErrors(['public_reason', 'duration_days']);

    expect($target->fresh()->status)->toBe(UserStatus::Active);
});

it('rejects a suspension longer than the configured maximum', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['username' => 'gimli']);

    $this->actingAs($admin)->post(route('admin.users.sanctions.store', 'gimli'), [
        'type' => 'suspension', 'reason_code' => 'spam', 'public_reason' => 'x', 'duration_days' => 999,
    ])->assertSessionHasErrors('duration_days');
});

it('lifts sanctions and reinstates the account', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->suspended()->create(['username' => 'theoden', 'status_reason' => 'under Saruman']);

    $this->actingAs($admin)->post(route('admin.users.sanctions.store', 'theoden'), [
        'type' => 'restriction', 'reason_code' => 'harassment', 'public_reason' => 'cooling off', 'duration_days' => 3,
    ])->assertRedirect();

    $this->actingAs($admin)->delete(route('admin.users.sanctions.destroy', 'theoden'), [
        'reason' => 'Appeal upheld.',
    ])->assertRedirect(route('admin.users.show', 'theoden'));

    expect($target->fresh()->status)->toBe(UserStatus::Active);
    $this->assertDatabaseHas('audit_logs', ['action' => 'user.sanction_lifted', 'auditable_id' => $target->id]);
    $this->assertDatabaseMissing('user_sanctions', ['user_id' => $target->id, 'lifted_at' => null]);
});

it('cannot sanction a peer, a superior, or oneself (structural rule 1)', function () {
    $admin = User::factory()->admin()->create();
    $peer = User::factory()->admin()->create(['username' => 'peer']);
    $superior = User::factory()->superAdmin()->create(['username' => 'boss']);

    foreach (['peer', 'boss', $admin->username] as $handle) {
        $this->actingAs($admin)->post(route('admin.users.sanctions.store', $handle), [
            'type' => 'suspension', 'reason_code' => 'spam', 'public_reason' => 'x', 'duration_days' => 1,
        ])->assertForbidden();
    }

    expect($peer->fresh()->status)->toBe(UserStatus::Active)
        ->and($superior->fresh()->status)->toBe(UserStatus::Active)
        ->and($admin->fresh()->status)->toBe(UserStatus::Active);
});
