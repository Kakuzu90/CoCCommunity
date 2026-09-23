<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\PlayerAccounts\Enums\AccountState;
use App\Modules\PlayerAccounts\Models\CocAccount;
use Illuminate\Support\Facades\Gate;

test('only the owner may view or verify their account', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $account = CocAccount::create([
        'user_id' => $owner->id,
        'tag' => '#2P0YQRL8V',
        'state' => AccountState::Unverified,
    ]);

    expect(Gate::forUser($owner)->allows('verify', $account))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('update', $account))->toBeTrue()
        ->and(Gate::forUser($other)->allows('verify', $account))->toBeFalse()
        ->and(Gate::forUser($other)->allows('view', $account))->toBeFalse();
});
