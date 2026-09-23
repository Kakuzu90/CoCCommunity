<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\PlayerAccounts\Actions\RequestOwnershipVerification;
use App\Modules\PlayerAccounts\Enums\AccountState;
use App\Modules\PlayerAccounts\Events\AccountOwnershipTransferred;
use App\Modules\PlayerAccounts\Events\AccountVerified;
use App\Modules\PlayerAccounts\Models\CocAccount;
use Illuminate\Support\Facades\Event;

const TAG = '#2P0YQRL8V';

test('a valid token verifies the account and records a snapshot', function (): void {
    $user = User::factory()->create();
    $fake = fakeClash();
    $fake->definePlayer(playerData(TAG), token: 'good-token');

    app(RequestOwnershipVerification::class)->handle($user->id, TAG, 'good-token');

    $account = CocAccount::where('tag', TAG)->firstOrFail();

    expect($account->user_id)->toBe($user->id)
        ->and($account->state)->toBe(AccountState::Verified)
        ->and($account->verified_at)->not->toBeNull()
        ->and($account->ign)->toBe('NightWitch')
        ->and($account->snapshots()->count())->toBe(1);
});

test('an invalid token leaves the account unverified and notifies the user', function (): void {
    $user = User::factory()->create();
    $fake = fakeClash();
    $fake->definePlayer(playerData(TAG), token: 'good-token');

    app(RequestOwnershipVerification::class)->handle($user->id, TAG, 'wrong-token');

    expect(CocAccount::where('tag', TAG)->exists())->toBeFalse();
    $this->assertDatabaseHas('notifications', [
        'user_id' => $user->id,
        'type' => 'coc.verification_failed',
    ]);
});

test('a new owner proving the token transfers ownership with an audit log and notice', function (): void {
    $previous = User::factory()->create();
    $claimant = User::factory()->create();
    $fake = fakeClash();
    $fake->definePlayer(playerData(TAG), token: 'claimant-token');

    $account = CocAccount::create([
        'user_id' => $previous->id,
        'tag' => TAG,
        'state' => AccountState::Verified,
        'verified_at' => now(),
    ]);

    app(RequestOwnershipVerification::class)->handle($claimant->id, TAG, 'claimant-token');

    $account->refresh();

    expect($account->user_id)->toBe($claimant->id)
        ->and($account->state)->toBe(AccountState::Verified);

    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $claimant->id,
        'action' => 'coc_account.ownership_transferred',
        'subject_id' => $account->id,
    ]);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $previous->id,
        'type' => 'coc.ownership_lost',
    ]);
});

test('verification emits domain events', function (): void {
    Event::fake([AccountVerified::class, AccountOwnershipTransferred::class]);

    $user = User::factory()->create();
    $fake = fakeClash();
    $fake->definePlayer(playerData(TAG), token: 'good-token');

    app(RequestOwnershipVerification::class)->handle($user->id, TAG, 'good-token');

    Event::assertDispatched(AccountVerified::class);
    Event::assertNotDispatched(AccountOwnershipTransferred::class);
});
