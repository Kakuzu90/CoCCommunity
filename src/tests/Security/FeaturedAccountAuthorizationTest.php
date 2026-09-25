<?php

use App\Domain\Auth\Models\User;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Livewire\Accounts\ManageAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function featureTarget(int $userId, string $tag = '#2PP'): CocAccount
{
    $account = new CocAccount(['ign' => 'Target', 'th_level' => 15, 'trophies' => 4000]);
    $account->forceFill([
        'ulid' => (string) Str::ulid(), 'user_id' => $userId, 'tag' => $tag, 'tag_normalized' => ltrim($tag, '#'),
        'status' => 'verified', 'verified_at' => now(), 'verification_method' => 'api_token',
    ])->save();

    return $account;
}

it('forbids suspended and email-unverified users from changing the featured account', function () {
    foreach (['#2PP' => User::factory()->suspended()->create(), '#9VU' => User::factory()->unverified()->create()] as $tag => $user) {
        $account = featureTarget($user->id, $tag);
        Livewire::actingAs($user)->test(ManageAccounts::class)->call('feature', $account->id)->assertForbidden();
        expect($account->fresh()->is_featured)->toBeFalse();
    }
});

it('never lets a user feature someone else\'s account by id', function () {
    $victim = User::factory()->create();
    $account = featureTarget($victim->id);

    Livewire::actingAs(User::factory()->create())->test(ManageAccounts::class)
        ->call('feature', $account->id)->assertNotFound();

    expect($account->fresh()->is_featured)->toBeFalse();
    $this->assertDatabaseMissing('audit_logs', ['action' => 'coc_account.featured']);
});
