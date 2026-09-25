<?php

use App\Domain\Auth\Models\User;
use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\Testing\FakeCocApiClient;
use App\Domain\PlayerAccounts\Services\AccountAttachService;
use App\Livewire\Accounts\ManageAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('requires authentication to reach the accounts surface', function () {
    $this->get(route('accounts.index'))->assertRedirect(route('login'));
});

it('grants the write gate only to active, verified accounts', function () {
    expect(Gate::forUser(User::factory()->create())->allows('manage-own-coc-accounts'))->toBeTrue()
        ->and(Gate::forUser(User::factory()->suspended()->create())->allows('manage-own-coc-accounts'))->toBeFalse()
        ->and(Gate::forUser(User::factory()->unverified()->create())->allows('manage-own-coc-accounts'))->toBeFalse();
});

it('forbids a suspended user from attaching through the component', function () {
    app()->instance(CocApiClient::class, new FakeCocApiClient);
    $suspended = User::factory()->suspended()->create();

    Livewire::actingAs($suspended)->test(ManageAccounts::class)
        ->set('tag', '#2PP')
        ->call('lookup')
        ->assertForbidden();
});

it('does not let one user verify a tag into another account', function () {
    // The service always keys the new/updated row by the acting user id, so a verification can only
    // ever attach the tag to the caller — never to an id supplied from the request.
    app()->instance(CocApiClient::class, (new FakeCocApiClient)->verifyReturns(true));
    $attacker = User::factory()->create();
    $victim = User::factory()->create();

    app(AccountAttachService::class)->verify($attacker->id, '#2PP', 'ok');

    $this->assertDatabaseHas('coc_accounts', ['tag_normalized' => '2PP', 'user_id' => $attacker->id]);
    $this->assertDatabaseMissing('coc_accounts', ['tag_normalized' => '2PP', 'user_id' => $victim->id]);
});
