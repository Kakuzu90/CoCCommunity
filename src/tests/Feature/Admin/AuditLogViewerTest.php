<?php

use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Apply one sanction so there is an audit entry to view. */
function seedAuditEntry(User $admin, string $handle): void
{
    User::factory()->create(['username' => $handle]);
    test()->actingAs($admin)->post(route('admin.users.sanctions.store', $handle), [
        'type' => 'ban', 'reason_code' => 'account_trading', 'public_reason' => 'Selling accounts.',
    ])->assertRedirect();
}

it('lets an admin view the audit log', function () {
    $admin = User::factory()->admin()->create();
    seedAuditEntry($admin, 'grima');

    $this->actingAs($admin)->get(route('admin.logs.index'))
        ->assertOk()
        ->assertSee('User banned')
        ->assertSee('grima');
});

it('filters the audit log by action and actor', function () {
    $admin = User::factory()->admin()->create(['username' => 'gandalf']);
    seedAuditEntry($admin, 'grima');

    $this->actingAs($admin)->get(route('admin.logs.index', ['action' => 'user.banned']))
        ->assertOk()->assertSee('grima');

    $this->actingAs($admin)->get(route('admin.logs.index', ['actor' => 'nobody']))
        ->assertOk()->assertDontSee('grima');
});

it('forbids a moderator from the audit log viewer', function () {
    $this->actingAs(User::factory()->moderator()->create())
        ->get(route('admin.logs.index'))->assertForbidden();
});

it('redirects a guest from the audit log viewer to login', function () {
    $this->get(route('admin.logs.index'))->assertRedirect(route('login'));
});
