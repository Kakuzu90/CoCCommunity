<?php

use App\Domain\Auth\Models\User;
use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\Testing\FakeCocApiClient;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Services\DisputeService;
use App\Livewire\Accounts\ManageAccounts;
use App\Livewire\Accounts\ManageDisputes;
use App\Livewire\Admin\ResolveDispute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function bindCoc(): void
{
    app()->instance(CocApiClient::class, (new FakeCocApiClient)->verifyReturns(true));
}

function seedHeld(int $userId, string $tag = '#2PP'): CocAccount
{
    $account = new CocAccount(['ign' => 'Holder', 'th_level' => 15, 'trophies' => 5000]);
    $account->forceFill([
        'ulid' => (string) Str::ulid(),
        'user_id' => $userId,
        'tag' => $tag,
        'tag_normalized' => ltrim($tag, '#'),
        'status' => CocAccountStatus::Verified->value,
        'verified_at' => now(),
        'verification_method' => 'api_token',
        'is_featured' => true,
    ])->save();

    return $account;
}

it('opens a dispute from the conflict on the accounts page', function () {
    bindCoc();
    $holder = User::factory()->create();
    seedHeld($holder->id, '#2PP');
    $claimant = User::factory()->create();

    Livewire::actingAs($claimant)->test(ManageAccounts::class)
        ->set('tag', '#2PP')
        ->call('lookup')
        ->assertSet('preview.conflictHolder', $holder->username)
        ->call('startDispute')
        ->assertSet('openingDispute', true)
        ->set('disputeReason', 'This is my account. I recovered it through Supercell support last week.')
        ->call('openDispute')
        ->assertHasNoErrors()
        ->assertSet('preview', null);

    $this->assertDatabaseHas('coc_account_disputes', ['tag_normalized' => '2PP', 'claimant_id' => $claimant->id, 'status' => 'awaiting_holder']);
});

it('validates the dispute reason length', function () {
    bindCoc();
    $holder = User::factory()->create();
    seedHeld($holder->id, '#2PP');

    Livewire::actingAs(User::factory()->create())->test(ManageAccounts::class)
        ->set('tag', '#2PP')
        ->call('lookup')
        ->call('startDispute')
        ->set('disputeReason', 'too short')
        ->call('openDispute')
        ->assertHasErrors('disputeReason');
});

it('shows a holder their incoming disputes and lets them release the tag', function () {
    $holder = User::factory()->create();
    $account = seedHeld($holder->id, '#2PP');
    $claimant = User::factory()->create();
    $summary = app(DisputeService::class)->open($claimant, '#2PP', str_repeat('mine ', 6));

    Livewire::actingAs($holder)->test(ManageDisputes::class)
        ->assertSee('#2PP')
        ->call('release', $summary->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('coc_accounts', ['user_id' => $claimant->id, 'status' => 'verified']);
});

it('lets a claimant withdraw their own dispute', function () {
    $holder = User::factory()->create();
    $account = seedHeld($holder->id, '#2PP');
    $claimant = User::factory()->create();
    $summary = app(DisputeService::class)->open($claimant, '#2PP', str_repeat('mine ', 6));

    Livewire::actingAs($claimant)->test(ManageDisputes::class)
        ->call('confirmWithdraw', $summary->id)
        ->call('withdraw')
        ->assertHasNoErrors();

    expect($account->fresh()->status)->toBe(CocAccountStatus::Verified);
    $this->assertDatabaseHas('coc_account_disputes', ['id' => $summary->id, 'status' => 'withdrawn']);
});

it('keeps the admin disputes queue behind the admin role', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.disputes.index'))->assertForbidden();
    $this->actingAs(User::factory()->moderator()->create())->get(route('admin.disputes.index'))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.disputes.index'))->assertOk()->assertSee('Ownership disputes');
});

it('shows a dispute to an admin and resolves it with a transfer', function () {
    $holder = User::factory()->create();
    $account = seedHeld($holder->id, '#2PP');
    $claimant = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $summary = app(DisputeService::class)->open($claimant, '#2PP', str_repeat('mine ', 6));

    $this->actingAs($admin)->get(route('admin.disputes.show', $summary->ulid))->assertOk()->assertSee('Review dispute');

    Livewire::actingAs($admin)->test(ResolveDispute::class, ['disputeId' => $summary->id])
        ->set('note', 'Evidence matches our snapshot history.')
        ->call('transfer')
        ->assertSet('resolved', true);

    $this->assertDatabaseHas('coc_accounts', ['user_id' => $claimant->id, 'status' => 'verified', 'verification_method' => 'admin']);
});

it('blocks a resolve panel action without the note', function () {
    $holder = User::factory()->create();
    seedHeld($holder->id, '#2PP');
    $claimant = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $summary = app(DisputeService::class)->open($claimant, '#2PP', str_repeat('mine ', 6));

    Livewire::actingAs($admin)->test(ResolveDispute::class, ['disputeId' => $summary->id])
        ->call('deny')
        ->assertHasErrors('note');
});
