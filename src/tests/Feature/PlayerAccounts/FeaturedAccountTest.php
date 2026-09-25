<?php

use App\Domain\Auth\Models\User;
use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\Testing\FakeCocApiClient;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Services\AccountDetachService;
use App\Domain\PlayerAccounts\Services\FeaturedAccountService;
use App\Livewire\Accounts\ManageAccounts;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function featuredSeed(int $userId, string $tag, bool $featured = false, CocAccountStatus $status = CocAccountStatus::Verified, ?CarbonInterface $verifiedAt = null): CocAccount
{
    $account = new CocAccount(['ign' => 'Chief '.$tag, 'th_level' => 15, 'trophies' => 4000]);
    $account->forceFill([
        'ulid' => (string) Str::ulid(), 'user_id' => $userId, 'tag' => $tag,
        'tag_normalized' => ltrim($tag, '#'), 'status' => $status->value,
        'verified_at' => $verifiedAt ?? now(), 'verification_method' => 'api_token', 'is_featured' => $featured,
    ])->save();

    return $account;
}

it('lets the owner move the featured flag to another verified account', function () {
    $user = User::factory()->create();
    $first = featuredSeed($user->id, '#2PP', featured: true);
    $second = featuredSeed($user->id, '#9VU');

    Livewire::actingAs($user)->test(ManageAccounts::class)
        ->assertSee('Set as featured')
        ->call('feature', $second->id)
        ->assertSet('flash', fn ($flash) => str_contains($flash, 'Featured account updated'));

    expect($first->fresh()->is_featured)->toBeFalse()
        ->and($second->fresh()->is_featured)->toBeTrue()
        ->and(CocAccount::where('user_id', $user->id)->where('is_featured', true)->count())->toBe(1);
    $this->assertDatabaseHas('audit_logs', ['actor_id' => $user->id, 'action' => 'coc_account.featured', 'auditable_id' => $second->id]);
});

it('returns 404 when featuring an unverified or foreign account', function () {
    $user = User::factory()->create();
    $unverified = featuredSeed($user->id, '#2PP', status: CocAccountStatus::Unverified);
    $foreign = featuredSeed(User::factory()->create()->id, '#9VU');

    Livewire::actingAs($user)->test(ManageAccounts::class)->call('feature', $unverified->id)->assertNotFound();
    Livewire::actingAs($user)->test(ManageAccounts::class)->call('feature', $foreign->id)->assertNotFound();

    expect($unverified->fresh()->is_featured)->toBeFalse()->and($foreign->fresh()->is_featured)->toBeFalse();
});

it('promotes the most recently verified account when the featured one is detached', function () {
    $user = User::factory()->create();
    $user->forceFill(['verified_accounts_count' => 3])->save();
    $featured = featuredSeed($user->id, '#2PP', featured: true);
    $older = featuredSeed($user->id, '#9VU', verifiedAt: now()->subWeek());
    $newer = featuredSeed($user->id, '#8QQ', verifiedAt: now()->subDay());

    app(AccountDetachService::class)->detach($user->id, $featured->id);

    expect($newer->fresh()->is_featured)->toBeTrue()->and($older->fresh()->is_featured)->toBeFalse();
});

it('keeps a disputed featured account and moves a suspended one', function () {
    $user = User::factory()->create();
    $disputed = featuredSeed($user->id, '#2PP', featured: true, status: CocAccountStatus::Disputed);
    $other = featuredSeed($user->id, '#9VU');
    $service = app(FeaturedAccountService::class);

    $service->reconcile($user->id);
    expect($disputed->fresh()->is_featured)->toBeTrue();

    $disputed->forceFill(['status' => CocAccountStatus::Suspended->value])->save();
    $service->reconcile($user->id);
    expect($disputed->fresh()->is_featured)->toBeFalse()->and($other->fresh()->is_featured)->toBeTrue();
});

it('does not double-feature when the user verifies a tag while their featured account is under review', function () {
    app()->instance(CocApiClient::class, (new FakeCocApiClient)->verifyReturns(true));
    $user = User::factory()->create();
    $disputed = featuredSeed($user->id, '#9VU', featured: true, status: CocAccountStatus::Disputed);

    Livewire::actingAs($user)->test(ManageAccounts::class)
        ->set('tag', '#2PP')->call('lookup')
        ->set('token', 'fresh-token')->call('verify')
        ->assertSet('preview', null);

    expect($disputed->fresh()->is_featured)->toBeTrue()
        ->and(CocAccount::where('user_id', $user->id)->where('tag_normalized', '2PP')->value('is_featured'))->toBeFalse();
});

it('moves a superseded holder\'s featured flag to their next verified account', function () {
    app()->instance(CocApiClient::class, (new FakeCocApiClient)->verifyReturns(true));
    $holder = User::factory()->create();
    $holder->forceFill(['verified_accounts_count' => 2])->save();
    $lost = featuredSeed($holder->id, '#2PP', featured: true);
    $kept = featuredSeed($holder->id, '#9VU');
    $claimant = User::factory()->create();

    Livewire::actingAs($claimant)->test(ManageAccounts::class)
        ->set('tag', '#2PP')->call('lookup')
        ->set('token', 'fresh-token')->call('verify');

    expect($lost->fresh()->is_featured)->toBeFalse()
        ->and($kept->fresh()->is_featured)->toBeTrue()
        ->and(CocAccount::where('user_id', $claimant->id)->where('is_featured', true)->count())->toBe(1);
});
