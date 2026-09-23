<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\CocIntegration\Exceptions\InvalidTagException;
use App\Modules\PlayerAccounts\Actions\LinkAccount;
use App\Modules\PlayerAccounts\Enums\AccountState;
use App\Modules\PlayerAccounts\Exceptions\TagAlreadyClaimedException;
use App\Modules\PlayerAccounts\Models\CocAccount;

test('it links a free tag as an unverified account', function (): void {
    $user = User::factory()->create();

    $account = app(LinkAccount::class)->handle($user, '2p0yqrl8v');

    expect($account->tag)->toBe('#2P0YQRL8V')
        ->and($account->state)->toBe(AccountState::Unverified)
        ->and($account->user_id)->toBe($user->id);
});

test('re-linking the same tag returns the existing account', function (): void {
    $user = User::factory()->create();
    $first = app(LinkAccount::class)->handle($user, '#2P0YQRL8V');
    $second = app(LinkAccount::class)->handle($user, '#2P0YQRL8V');

    expect($second->id)->toBe($first->id)
        ->and(CocAccount::where('tag', '#2P0YQRL8V')->count())->toBe(1);
});

test('linking a tag held by another user is refused', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    app(LinkAccount::class)->handle($owner, '#2P0YQRL8V');

    expect(fn () => app(LinkAccount::class)->handle($other, '#2P0YQRL8V'))
        ->toThrow(TagAlreadyClaimedException::class);
});

test('an invalid tag is rejected', function (): void {
    $user = User::factory()->create();

    expect(fn () => app(LinkAccount::class)->handle($user, '#ABC123'))
        ->toThrow(InvalidTagException::class);
});
