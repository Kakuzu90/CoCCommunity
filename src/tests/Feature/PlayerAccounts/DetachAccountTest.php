<?php

use App\Domain\Auth\Models\User;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Services\AccountDetachService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function seedOwned(int $userId, string $tag): CocAccount
{
    $account = new CocAccount(['ign' => 'Chief', 'th_level' => 15, 'trophies' => 4000]);
    $account->forceFill([
        'ulid' => (string) Str::ulid(),
        'user_id' => $userId,
        'tag' => $tag,
        'tag_normalized' => ltrim($tag, '#'),
        'status' => 'verified',
        'verified_at' => now(),
        'verification_method' => 'api_token',
        'is_featured' => true,
    ])->save();

    return $account;
}

it('detaches a verified account, releases the tag and decrements the counter', function () {
    $user = User::factory()->create();
    $user->forceFill(['verified_accounts_count' => 1])->save();
    $account = seedOwned($user->id, '#2PP');

    app(AccountDetachService::class)->detach($user->id, $account->id);

    $this->assertDatabaseHas('coc_accounts', [
        'id' => $account->id, 'status' => 'released', 'user_id' => null, 'is_featured' => false,
    ]);
    expect($user->fresh()->verified_accounts_count)->toBe(0);
    $this->assertDatabaseHas('audit_logs', ['actor_id' => $user->id, 'action' => 'coc_account.detached', 'auditable_id' => $account->id]);
    $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->id]);
});

it('refuses to detach an account that belongs to another user', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $account = seedOwned($owner->id, '#2PP');

    expect(fn () => app(AccountDetachService::class)->detach($stranger->id, $account->id))
        ->toThrow(ModelNotFoundException::class);

    $this->assertDatabaseHas('coc_accounts', ['id' => $account->id, 'status' => 'verified', 'user_id' => $owner->id]);
});
