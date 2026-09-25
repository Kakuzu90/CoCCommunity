<?php

use App\Domain\Auth\Models\User;
use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\Enums\CocErrorReason;
use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Domain\CocIntegration\Testing\FakeCocApiClient;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Enums\RefreshOutcome;
use App\Domain\PlayerAccounts\Enums\SnapshotSource;
use App\Domain\PlayerAccounts\Enums\SyncTier;
use App\Domain\PlayerAccounts\Jobs\SyncCocAccountJob;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Models\CocAccountSnapshot;
use App\Domain\PlayerAccounts\Models\SyncState;
use App\Domain\PlayerAccounts\Services\AccountSyncService;
use App\Domain\PlayerAccounts\Services\ManualAccountRefresh;
use App\Domain\PlayerAccounts\Services\SnapshotCompactor;
use App\Domain\PlayerAccounts\Services\SnapshotStore;
use App\Livewire\Accounts\ManageAccounts;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function syncAccount(User $user, string $tag = '#2PP', bool $verified = true): CocAccount
{
    $account = new CocAccount(['ign' => 'Chief', 'th_level' => 15, 'trophies' => 4000]);
    $account->forceFill([
        'ulid' => (string) Str::ulid(), 'user_id' => $user->id, 'tag' => $tag,
        'tag_normalized' => ltrim($tag, '#'),
        'status' => $verified ? CocAccountStatus::Verified->value : CocAccountStatus::Unverified->value,
        'verified_at' => $verified ? now() : null,
        'verification_method' => $verified ? 'api_token' : null,
    ])->save();

    return $account;
}

it('assigns tiers from owner activity and featured status', function () {
    $user = User::factory()->create(['last_login_at' => now()->subDays(15)]);
    $account = syncAccount($user);
    $sync = app(AccountSyncService::class);
    $sync->initialize($account->id);

    $state = SyncState::where('resource_id', $account->id)->firstOrFail();
    expect($state->tier)->toBe(SyncTier::Warm)
        ->and($state->next_due_at->diffInSeconds(now(), true))->toBeLessThanOrEqual((float) config('coc.sync.tiers.warm'));

    $account->forceFill(['is_featured' => true])->save();
    expect($sync->tierFor($account, $state))->toBe(SyncTier::Hot);

    $account->forceFill(['is_featured' => false])->save();
    $user->forceFill(['last_login_at' => now()->subDays(60)])->save();
    expect($sync->tierFor($account, $state))->toBe(SyncTier::Cold);
});

it('writes a snapshot only when tracked progression changes', function () {
    $user = User::factory()->create();
    $account = syncAccount($user);
    $fake = new FakeCocApiClient;
    app()->instance(CocApiClient::class, $fake);
    $sync = app(AccountSyncService::class);
    $sync->initialize($account->id);

    expect(CocAccountSnapshot::where('coc_account_id', $account->id)->count())->toBe(1);
    $sync->sync($account->id);
    expect(CocAccountSnapshot::where('coc_account_id', $account->id)->count())->toBe(2);
    $sync->sync($account->id);
    expect(CocAccountSnapshot::where('coc_account_id', $account->id)->count())->toBe(2);

    $fake->stubArray('#2PP', ['name' => 'Renamed Chief', 'townHallLevel' => 15, 'trophies' => 4200]);
    $sync->sync($account->id);
    expect(CocAccountSnapshot::where('coc_account_id', $account->id)->count())->toBe(3)
        ->and($account->fresh()->ign)->toBe('Renamed Chief');
});

it('keeps verification and saved data through repeated not-found responses', function () {
    $user = User::factory()->create();
    $account = syncAccount($user);
    $fake = (new FakeCocApiClient)->throwFor('#2PP', CocApiException::notFound('#2PP'));
    app()->instance(CocApiClient::class, $fake);
    $sync = app(AccountSyncService::class);
    $sync->initialize($account->id);

    for ($attempt = 0; $attempt < (int) config('coc.sync.not_found_stale_after'); $attempt++) {
        try {
            $sync->sync($account->id);
        } catch (CocApiException) {
        }
    }

    $state = SyncState::where('resource_id', $account->id)->firstOrFail();
    expect($account->fresh()->status)->toBe(CocAccountStatus::Verified)
        ->and($account->fresh()->ign)->toBe('Chief')
        ->and($state->stale)->toBeTrue()
        ->and($state->not_found_failures)->toBe((int) config('coc.sync.not_found_stale_after'));
    $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->id]);
});

it('freezes after repeated failures and stops after the weekly retry fails', function () {
    $account = syncAccount(User::factory()->create());
    app()->instance(CocApiClient::class, (new FakeCocApiClient)->throwFor(
        '#2PP', new CocApiException(CocErrorReason::ServerError, 'Unavailable.', 500),
    ));
    $sync = app(AccountSyncService::class);
    $sync->initialize($account->id);

    for ($attempt = 0; $attempt < (int) config('coc.sync.freeze_after') + 1; $attempt++) {
        try {
            $sync->sync($account->id);
        } catch (CocApiException) {
        }
    }

    $state = SyncState::where('resource_id', $account->id)->firstOrFail();
    expect($state->tier)->toBe(SyncTier::Frozen)
        ->and($state->flagged)->toBeTrue()
        ->and($state->next_due_at)->toBeNull()
        ->and($account->fresh()->status)->toBe(CocAccountStatus::Verified);
});

it('dispatches due verified accounts within the configured background budget', function () {
    Bus::fake();
    $user = User::factory()->create();
    $verified = syncAccount($user);
    syncAccount($user, '#2P0', false);
    $sync = app(AccountSyncService::class);
    $sync->initialize($verified->id);
    SyncState::where('resource_id', $verified->id)->update(['next_due_at' => now()->subMinute()]);

    $this->artisan('coc:sync-accounts')->assertSuccessful();
    Bus::assertDispatched(SyncCocAccountJob::class, fn (SyncCocAccountJob $job): bool => $job->accountId === $verified->id);
    Bus::assertDispatchedTimes(SyncCocAccountJob::class, 1);
});

it('keeps the job-computed due time when the queue runs synchronously', function () {
    app()->instance(CocApiClient::class, new FakeCocApiClient);
    $account = syncAccount(User::factory()->create());
    app(AccountSyncService::class)->initialize($account->id);
    SyncState::where('resource_id', $account->id)->update(['next_due_at' => now()->subMinute()]);

    $this->artisan('coc:sync-accounts')->assertSuccessful();

    $state = SyncState::where('resource_id', $account->id)->firstOrFail();
    expect($state->last_success_at)->not->toBeNull()
        ->and($state->next_due_at->greaterThan(now()->addSeconds((int) config('coc.sync.scheduler_seconds'))))->toBeTrue();
});

it('scopes manual refresh to the owner and rate limits it', function () {
    app()->instance(CocApiClient::class, new FakeCocApiClient);
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $account = syncAccount($owner);
    $service = app(ManualAccountRefresh::class);

    expect(fn () => $service->refresh($other->id, $account->id))->toThrow(ModelNotFoundException::class);
    Livewire::actingAs($owner)->test(ManageAccounts::class)
        ->call('refreshAccount', $account->id)
        ->assertSet('flash', 'Game data refreshed.')
        ->call('refreshAccount', $account->id)
        ->assertHasErrors('refresh');

    $this->assertDatabaseHas('coc_account_snapshots', ['coc_account_id' => $account->id, 'source' => 'manual']);
});

it('lets an unverified holder refresh manually without background scheduling or gaining verification', function () {
    app()->instance(CocApiClient::class, new FakeCocApiClient);
    $owner = User::factory()->create();
    $account = syncAccount($owner, '#2PP', false);

    Livewire::actingAs($owner)->test(ManageAccounts::class)
        ->assertSee('Refresh')
        ->call('refreshAccount', $account->id)
        ->assertSet('flash', 'Game data refreshed.');

    expect($account->fresh()->status)->toBe(CocAccountStatus::Unverified);
    $this->artisan('coc:sync-accounts')->assertSuccessful();
    $this->assertDatabaseHas('coc_account_snapshots', ['coc_account_id' => $account->id, 'source' => 'manual']);
});

it('queues a sync when the interactive refresh times out', function () {
    Bus::fake();
    $owner = User::factory()->create();
    $account = syncAccount($owner);
    app()->instance(CocApiClient::class, (new FakeCocApiClient)->throwFor(
        '#2PP', new CocApiException(CocErrorReason::Timeout, 'Timed out.'),
    ));

    expect(app(ManualAccountRefresh::class)->refresh($owner->id, $account->id))->toBe(RefreshOutcome::Queued);
    Bus::assertDispatched(SyncCocAccountJob::class, fn (SyncCocAccountJob $job): bool => $job->accountId === $account->id);
});

it('moves a viewed account to hot without reviving frozen syncs', function () {
    $user = User::factory()->create(['last_login_at' => now()->subDays(60)]);
    $account = syncAccount($user);
    $sync = app(AccountSyncService::class);
    $sync->initialize($account->id);
    $sync->recordView($account->id);

    $state = SyncState::where('resource_id', $account->id)->firstOrFail();
    expect($state->tier)->toBe(SyncTier::Hot)->and($state->viewed_at)->not->toBeNull();

    $state->forceFill(['tier' => SyncTier::Frozen->value, 'next_due_at' => null])->save();
    $sync->recordView($account->id);
    expect($state->fresh()->tier)->toBe(SyncTier::Frozen)
        ->and($state->fresh()->next_due_at)->toBeNull();
});

it('compacts old snapshots while keeping recent changes and the latest fallback', function () {
    $account = syncAccount(User::factory()->create());
    $store = app(SnapshotStore::class);
    $store->capture($account->fresh(), SnapshotSource::Verification);
    $base = CocAccountSnapshot::where('coc_account_id', $account->id)->firstOrFail();

    foreach ([400, 400, 150, 150, 1] as $offset => $days) {
        CocAccountSnapshot::query()->create([
            'coc_account_id' => $account->id,
            'captured_at' => now()->subDays($days)->addMinutes($offset),
            'th_level' => 15,
            'xp_level' => 100,
            'trophies' => 4000 + $offset,
            'best_trophies' => 5000,
            'war_stars' => 100,
            'attack_wins' => 50,
            'defense_wins' => 20,
            'donations' => 10,
            'heroes' => [], 'troops' => [], 'spells' => [], 'hero_equipment' => [],
            'source' => 'scheduled',
        ]);
    }

    $compactor = app(SnapshotCompactor::class);
    expect($compactor->compact(true))->toBe(2)
        ->and(CocAccountSnapshot::where('coc_account_id', $account->id)->count())->toBe(6);
    expect($compactor->compact())->toBe(2)
        ->and(CocAccountSnapshot::where('coc_account_id', $account->id)->count())->toBe(4);
    $this->assertDatabaseHas('coc_account_snapshots', ['id' => $base->id]);
});
