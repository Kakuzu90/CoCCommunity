<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\PlayerAccounts\Enums\AccountState;
use App\Modules\PlayerAccounts\Models\CocAccount;
use Livewire\Volt\Volt;

test('the accounts page requires a verified user', function (): void {
    $this->get('/accounts')->assertRedirect(route('login'));

    $this->actingAs(User::factory()->unverified()->create());
    $this->get('/accounts')->assertRedirect(route('verification.notice'));

    $this->actingAs(User::factory()->create());
    $this->get('/accounts')->assertOk();
});

test('a user can link then verify an account through the page', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $fake = fakeClash();
    $fake->definePlayer(playerData('#2P0YQRL8V'), token: 'good-token');

    $component = Volt::test('pages.accounts.index')
        ->set('tag', '#2P0YQRL8V')
        ->call('addAccount')
        ->assertHasNoErrors();

    $account = CocAccount::where('user_id', $user->id)->firstOrFail();
    expect($account->state)->toBe(AccountState::Unverified);

    $component->set("tokens.{$account->id}", 'good-token')
        ->call('verify', $account->id)
        ->assertHasNoErrors();

    expect($account->refresh()->state)->toBe(AccountState::Verified);
});

test('linking a tag another user holds shows an error', function (): void {
    $owner = User::factory()->create();
    CocAccount::create(['user_id' => $owner->id, 'tag' => '#2P0YQRL8V', 'state' => AccountState::Unverified]);

    $this->actingAs(User::factory()->create());

    Volt::test('pages.accounts.index')
        ->set('tag', '#2P0YQRL8V')
        ->call('addAccount')
        ->assertHasErrors('tag');
});
