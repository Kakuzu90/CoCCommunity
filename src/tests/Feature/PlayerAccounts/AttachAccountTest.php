<?php

use App\Domain\Auth\Models\User;
use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\Testing\FakeCocApiClient;
use App\Domain\PlayerAccounts\Enums\AttachError;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Exceptions\AccountAttachException;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Services\AccountAttachService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function fakeCoc(bool $verify = true): FakeCocApiClient
{
    $fake = (new FakeCocApiClient)->verifyReturns($verify);
    app()->instance(CocApiClient::class, $fake);

    return $fake;
}

function attach(): AccountAttachService
{
    return app(AccountAttachService::class);
}

function seedVerified(int $userId, string $tag, string $ign = 'Holder'): CocAccount
{
    $account = new CocAccount(['ign' => $ign, 'th_level' => 14, 'trophies' => 3000]);
    $account->forceFill([
        'ulid' => (string) Str::ulid(),
        'user_id' => $userId,
        'tag' => $tag,
        'tag_normalized' => ltrim($tag, '#'),
        'status' => CocAccountStatus::Verified->value,
        'verified_at' => now(),
        'verification_method' => 'api_token',
    ])->save();

    return $account;
}

it('verifies a tag with a token and attaches it as the featured account', function () {
    fakeCoc(verify: true);
    $user = User::factory()->create();

    $result = attach()->verify($user->id, '#2PP', 'good-token');

    expect($result->verified())->toBeTrue()->and($result->superseded)->toBeFalse();
    $this->assertDatabaseHas('coc_accounts', [
        'user_id' => $user->id, 'tag_normalized' => '2PP', 'status' => 'verified',
        'verification_method' => 'api_token', 'is_featured' => true,
    ]);
    $this->assertDatabaseHas('coc_account_claims', ['user_id' => $user->id, 'tag_normalized' => '2PP', 'status' => 'succeeded']);
    $this->assertDatabaseHas('audit_logs', ['actor_id' => $user->id, 'action' => 'coc_account.verified', 'auditable_type' => 'coc_account']);
    expect($user->fresh()->verified_accounts_count)->toBe(1);
});

it('returns an invalid outcome and logs a failed claim on a bad token', function () {
    fakeCoc(verify: false);
    $user = User::factory()->create();

    $result = attach()->verify($user->id, '#2PP', 'stale-token');

    expect($result->verified())->toBeFalse();
    $this->assertDatabaseHas('coc_account_claims', ['user_id' => $user->id, 'status' => 'failed', 'failure_reason' => 'invalid_token']);
    $this->assertDatabaseMissing('coc_accounts', ['tag_normalized' => '2PP']);
});

it('detects a conflict in the preview when the tag is verified by someone else', function () {
    fakeCoc();
    $holder = User::factory()->create(['username' => 'oldowner']);
    $newcomer = User::factory()->create();
    seedVerified($holder->id, '#2PP');

    $preview = attach()->preview($newcomer->id, '#2PP');

    expect($preview->hasConflict())->toBeTrue()->and($preview->conflictHolder)->toBe('oldowner');
});

it('supersedes the previous verified holder when a token proves present control', function () {
    fakeCoc(verify: true);
    $holder = User::factory()->create();
    $challenger = User::factory()->create();
    seedVerified($holder->id, '#2PP');
    $holder->forceFill(['verified_accounts_count' => 1])->save();

    $result = attach()->verify($challenger->id, '#2PP', 'good-token');

    expect($result->verified())->toBeTrue()->and($result->superseded)->toBeTrue();
    $this->assertDatabaseHas('coc_accounts', ['user_id' => $challenger->id, 'tag_normalized' => '2PP', 'status' => 'verified']);
    $this->assertDatabaseHas('coc_accounts', ['user_id' => $holder->id, 'tag_normalized' => '2PP', 'status' => 'unverified', 'previous_user_id' => $holder->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'coc_account.superseded', 'actor_id' => $challenger->id]);
    // The previous holder gets a security notification about the change.
    $this->assertDatabaseHas('notifications', ['notifiable_id' => $holder->id]);
});

it('refuses to add a tag the user has already attached', function () {
    fakeCoc();
    $user = User::factory()->create();
    seedVerified($user->id, '#2PP');

    expect(fn () => attach()->preview($user->id, '#2PP'))
        ->toThrow(AccountAttachException::class);
});

it('rejects a malformed tag before calling the API', function () {
    fakeCoc();
    $user = User::factory()->create();

    try {
        attach()->preview($user->id, 'not a tag!!');
        $this->fail('Expected an AccountAttachException.');
    } catch (AccountAttachException $e) {
        expect($e->error)->toBe(AttachError::InvalidTag);
    }
});

it('rate-limits verification and records the throttled attempt', function () {
    fakeCoc(verify: true);
    $user = User::factory()->create();
    for ($i = 0; $i < (int) config('coc.attach.verify_attempts_per_hour'); $i++) {
        RateLimiter::hit("coc-verify:{$user->id}", 3600);
    }

    try {
        attach()->verify($user->id, '#2PP', 'good-token');
        $this->fail('Expected a rate-limit exception.');
    } catch (AccountAttachException $e) {
        expect($e->error)->toBe(AttachError::RateLimited);
    }
    $this->assertDatabaseHas('coc_account_claims', ['user_id' => $user->id, 'status' => 'failed', 'failure_reason' => 'rate_limited']);
});
