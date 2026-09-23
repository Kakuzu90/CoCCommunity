<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\PlayerAccounts\Enums\AccountState;
use App\Modules\PlayerAccounts\Jobs\SyncCocAccount;
use App\Modules\PlayerAccounts\Models\CocAccount;

test('syncing records a snapshot and updates the account', function (): void {
    $user = User::factory()->create();
    $account = CocAccount::create([
        'user_id' => $user->id,
        'tag' => '#2P0YQRL8V',
        'state' => AccountState::Verified,
        'verified_at' => now(),
    ]);

    $fake = fakeClash();
    $fake->definePlayer(playerData('#2P0YQRL8V', ['trophies' => 6100]));

    app()->call([new SyncCocAccount($account->id), 'handle']);

    $account->refresh();

    expect($account->ign)->toBe('NightWitch')
        ->and($account->last_synced_at)->not->toBeNull()
        ->and($account->snapshots()->first()->trophies)->toBe(6100);
});

test('a tag that no longer resolves flips to needs_reverify without losing data', function (): void {
    $user = User::factory()->create();
    $account = CocAccount::create([
        'user_id' => $user->id,
        'tag' => '#2P0YQRL8V',
        'state' => AccountState::Verified,
        'verified_at' => now(),
    ]);

    fakeClash(); // no player defined -> PlayerNotFound

    app()->call([new SyncCocAccount($account->id), 'handle']);

    $account->refresh();

    expect($account->state)->toBe(AccountState::NeedsReverify)
        ->and($account->snapshots()->count())->toBe(0);
});
