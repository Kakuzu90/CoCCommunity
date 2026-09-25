<?php

use App\Domain\Auth\Models\User;
use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\Testing\FakeCocApiClient;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Livewire\Accounts\ManageAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function bindFake(bool $verify = true): void
{
    app()->instance(CocApiClient::class, (new FakeCocApiClient)->verifyReturns($verify));
}

it('redirects guests away from the accounts page', function () {
    $this->get(route('accounts.index'))->assertRedirect(route('login'));
});

it('lets a signed-in user open the accounts page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertSee('Your Clash of Clans accounts');
});

it('walks the tag lookup then token verification flow', function () {
    bindFake(verify: true);
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(ManageAccounts::class)
        ->set('tag', '#2PP')
        ->call('lookup')
        ->assertSet('preview.tag', '#2PP')
        ->assertSee('Is this you?')
        ->set('token', 'fresh-token')
        ->call('verify')
        ->assertSet('preview', null)
        ->assertSet('flash', fn ($flash) => str_contains($flash, 'Verified'));

    $this->assertDatabaseHas('coc_accounts', ['user_id' => $user->id, 'tag_normalized' => '2PP', 'status' => 'verified']);
});

it('shows a precise error when the token is rejected', function () {
    bindFake(verify: false);
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(ManageAccounts::class)
        ->set('tag', '#2PP')
        ->call('lookup')
        ->set('token', 'expired')
        ->call('verify')
        ->assertHasErrors('token')
        ->assertSet('preview.tag', '#2PP'); // stays on the token step to retry
});

it('detaches an account after the password is confirmed', function () {
    bindFake(verify: true);
    $user = User::factory()->create(); // factory password is "password"

    Livewire::actingAs($user)->test(ManageAccounts::class)
        ->set('tag', '#2PP')->call('lookup')->set('token', 'ok')->call('verify');

    $account = CocAccount::where('user_id', $user->id)->firstOrFail();

    Livewire::actingAs($user)->test(ManageAccounts::class)
        ->call('confirmDetach', $account->id)
        ->set('password', 'password')
        ->call('detach')
        ->assertSet('flash', fn ($flash) => str_contains($flash, 'released'));

    $this->assertDatabaseHas('coc_accounts', ['id' => $account->id, 'status' => 'released', 'user_id' => null]);
});

it('rejects a detach with the wrong password', function () {
    $user = User::factory()->create();
    $account = new CocAccount(['ign' => 'Chief', 'th_level' => 15, 'trophies' => 4000]);
    $account->forceFill([
        'ulid' => (string) Str::ulid(), 'user_id' => $user->id, 'tag' => '#2PP',
        'tag_normalized' => '2PP', 'status' => 'verified', 'verified_at' => now(), 'verification_method' => 'api_token',
    ])->save();

    Livewire::actingAs($user)->test(ManageAccounts::class)
        ->call('confirmDetach', $account->id)
        ->set('password', 'wrong-password')
        ->call('detach')
        ->assertHasErrors('password');

    $this->assertDatabaseHas('coc_accounts', ['id' => $account->id, 'status' => 'verified']);
});
