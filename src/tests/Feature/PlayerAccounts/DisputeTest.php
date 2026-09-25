<?php

use App\Domain\Auth\Models\User;
use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\Testing\FakeCocApiClient;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Enums\DisputeError;
use App\Domain\PlayerAccounts\Enums\DisputeStatus;
use App\Domain\PlayerAccounts\Enums\HolderResponse;
use App\Domain\PlayerAccounts\Exceptions\DisputeException;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Models\CocAccountDispute;
use App\Domain\PlayerAccounts\Services\AccountAttachService;
use App\Domain\PlayerAccounts\Services\DisputeResolutionService;
use App\Domain\PlayerAccounts\Services\DisputeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function disputeService(): DisputeService
{
    return app(DisputeService::class);
}

function resolutionService(): DisputeResolutionService
{
    return app(DisputeResolutionService::class);
}

function fakeCocClient(bool $verify = true): FakeCocApiClient
{
    $fake = (new FakeCocApiClient)->verifyReturns($verify);
    app()->instance(CocApiClient::class, $fake);

    return $fake;
}

function verifiedAccount(int $userId, string $tag = '#2PP', string $ign = 'Holder'): CocAccount
{
    $account = new CocAccount(['ign' => $ign, 'th_level' => 15, 'trophies' => 5200]);
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

/** @return array{0: User, 1: User, 2: CocAccount} holder, claimant, account */
function conflictSetup(string $tag = '#2PP'): array
{
    $holder = User::factory()->create();
    $claimant = User::factory()->create();
    $account = verifiedAccount($holder->id, $tag);

    return [$holder, $claimant, $account];
}

it('files a dispute against the verified holder and moves the tag under review', function () {
    [$holder, $claimant, $account] = conflictSetup();

    $summary = disputeService()->open($claimant, '#2PP', str_repeat('This account is mine, ', 3));

    expect($summary->status)->toBe(DisputeStatus::AwaitingHolder);
    $this->assertDatabaseHas('coc_account_disputes', [
        'tag_normalized' => '2PP', 'claimant_id' => $claimant->id,
        'current_holder_id' => $holder->id, 'status' => 'awaiting_holder',
    ]);
    expect($account->fresh()->status)->toBe(CocAccountStatus::Disputed);
    $this->assertDatabaseHas('coc_account_claims', ['user_id' => $claimant->id, 'method' => 'dispute', 'status' => 'pending']);
    $this->assertDatabaseHas('audit_logs', ['actor_id' => $claimant->id, 'action' => 'coc_dispute.opened']);
});

it('rejects a dispute on a tag no one has verified', function () {
    $claimant = User::factory()->create();

    disputeService()->open($claimant, '#2PP', str_repeat('mine ', 5));
})->throws(DisputeException::class);

it('rejects disputing your own tag', function () {
    [$holder] = conflictSetup();

    expect(fn () => disputeService()->open($holder, '#2PP', str_repeat('mine ', 5)))
        ->toThrow(DisputeException::class);
});

it('blocks a second dispute by the same claimant on the same tag', function () {
    [, $claimant] = conflictSetup();
    disputeService()->open($claimant, '#2PP', str_repeat('mine ', 5));

    try {
        disputeService()->open($claimant, '#2PP', str_repeat('again ', 5));
        $this->fail('expected DisputeException');
    } catch (DisputeException $e) {
        expect($e->error)->toBe(DisputeError::AlreadyDisputing);
    }
});

it('blocks a different claimant while the tag is under review', function () {
    [, $claimant] = conflictSetup();
    $other = User::factory()->create();
    disputeService()->open($claimant, '#2PP', str_repeat('mine ', 5));

    try {
        disputeService()->open($other, '#2PP', str_repeat('mine too ', 5));
        $this->fail('expected DisputeException');
    } catch (DisputeException $e) {
        expect($e->error)->toBe(DisputeError::UnderReview);
    }
});

it('caps the number of concurrent open disputes per user', function () {
    $claimant = User::factory()->create();
    verifiedAccount(User::factory()->create()->id, '#PYL');
    verifiedAccount(User::factory()->create()->id, '#QGR');
    verifiedAccount(User::factory()->create()->id, '#JCU');

    disputeService()->open($claimant, '#PYL', str_repeat('mine ', 5));
    disputeService()->open($claimant, '#QGR', str_repeat('mine ', 5));

    try {
        disputeService()->open($claimant, '#JCU', str_repeat('mine ', 5));
        $this->fail('expected DisputeException');
    } catch (DisputeException $e) {
        expect($e->error)->toBe(DisputeError::TooManyOpen);
    }
});

it('bars a claimant with two recent denied disputes', function () {
    $claimant = User::factory()->create();
    foreach (['#AAA', '#BBB'] as $tag) {
        $dispute = new CocAccountDispute([
            'ulid' => (string) Str::ulid(),
            'tag_normalized' => ltrim($tag, '#'),
            'reason' => 'x',
            'evidence' => [],
        ]);
        $dispute->forceFill([
            'coc_account_id' => verifiedAccount(User::factory()->create()->id, $tag)->id,
            'claimant_id' => $claimant->id,
            'status' => DisputeStatus::ResolvedDenied->value,
            'decided_at' => Carbon::now()->subDays(3),
        ])->save();
    }
    verifiedAccount(User::factory()->create()->id, '#CCC');

    try {
        disputeService()->open($claimant, '#CCC', str_repeat('mine ', 5));
        $this->fail('expected DisputeException');
    } catch (DisputeException $e) {
        expect($e->error)->toBe(DisputeError::Barred);
    }
});

it('lets an admin transfer the tag to the claimant with a full audit trail', function () {
    [$holder, $claimant, $account] = conflictSetup();
    $admin = User::factory()->admin()->create();
    $summary = disputeService()->open($claimant, '#2PP', str_repeat('mine ', 6));

    resolutionService()->transfer($admin->id, $summary->id, 'Claimant produced a receipt matching our snapshots.');

    $this->assertDatabaseHas('coc_accounts', ['user_id' => $claimant->id, 'tag_normalized' => '2PP', 'status' => 'verified', 'verification_method' => 'admin']);
    expect(CocAccount::query()->where('user_id', $holder->id)->where('tag_normalized', '2PP')->value('status'))->toBe(CocAccountStatus::Unverified);
    $this->assertDatabaseHas('coc_account_disputes', ['id' => $summary->id, 'status' => 'resolved_transfer', 'decided_by' => $admin->id]);
    $this->assertDatabaseHas('moderation_actions', ['actor_id' => $admin->id, 'action' => 'transfer_ownership', 'target_user_id' => $claimant->id]);
    $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'coc_dispute.transferred']);
    expect($claimant->fresh()->verified_accounts_count)->toBe(1)
        ->and($holder->fresh()->verified_accounts_count)->toBe(0);
});

it('lets an admin deny a dispute and return the tag to the holder', function () {
    [$holder, $claimant, $account] = conflictSetup();
    $admin = User::factory()->admin()->create();
    $summary = disputeService()->open($claimant, '#2PP', str_repeat('mine ', 6));

    resolutionService()->deny($admin->id, $summary->id, 'No decisive evidence; holder keeps the tag.');

    expect($account->fresh()->status)->toBe(CocAccountStatus::Verified);
    $this->assertDatabaseHas('coc_account_disputes', ['id' => $summary->id, 'status' => 'resolved_denied']);
    $this->assertDatabaseHas('moderation_actions', ['actor_id' => $admin->id, 'action' => 'dismiss']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'coc_dispute.denied']);
});

it('lets an admin suspend the tag when both parties look fraudulent', function () {
    [$holder, $claimant, $account] = conflictSetup();
    $admin = User::factory()->admin()->create();
    $summary = disputeService()->open($claimant, '#2PP', str_repeat('mine ', 6));

    resolutionService()->suspend($admin->id, $summary->id, 'Both accounts show trading behaviour.');

    expect($account->fresh()->status)->toBe(CocAccountStatus::Suspended);
    $this->assertDatabaseHas('audit_logs', ['action' => 'coc_dispute.tag_suspended']);
    expect($holder->fresh()->verified_accounts_count)->toBe(0);
});

it('prevents an admin who is a party from resolving the dispute', function () {
    [$holder, $claimant] = conflictSetup();
    // The claimant is themselves an admin.
    $claimant->forceFill(['role' => 'admin'])->save();
    $summary = disputeService()->open($claimant, '#2PP', str_repeat('mine ', 6));

    try {
        resolutionService()->deny($claimant->id, $summary->id, 'trying to self-resolve');
        $this->fail('expected DisputeException');
    } catch (DisputeException $e) {
        expect($e->error)->toBe(DisputeError::AdminIsParty);
    }
});

it('routes a holder counter-statement to the admin queue', function () {
    [$holder, $claimant] = conflictSetup();
    $summary = disputeService()->open($claimant, '#2PP', str_repeat('mine ', 6));

    disputeService()->respondByHolder($holder->id, $summary->id, HolderResponse::Counter, 'This has been my account for years.');

    $this->assertDatabaseHas('coc_account_disputes', ['id' => $summary->id, 'status' => 'open']);
});

it('transfers the tag when the holder voluntarily releases it', function () {
    [$holder, $claimant, $account] = conflictSetup();
    $summary = disputeService()->open($claimant, '#2PP', str_repeat('mine ', 6));

    disputeService()->respondByHolder($holder->id, $summary->id, HolderResponse::Release);

    $this->assertDatabaseHas('coc_accounts', ['user_id' => $claimant->id, 'status' => 'verified']);
    $this->assertDatabaseHas('coc_account_disputes', ['id' => $summary->id, 'status' => 'resolved_transfer']);
});

it('rejects a holder response from someone who is not the holder', function () {
    [, $claimant] = conflictSetup();
    $stranger = User::factory()->create();
    $summary = disputeService()->open($claimant, '#2PP', str_repeat('mine ', 6));

    expect(fn () => disputeService()->respondByHolder($stranger->id, $summary->id, HolderResponse::Release))
        ->toThrow(DisputeException::class);
});

it('auto-resolves the claimant dispute when they verify with a token', function () {
    fakeCocClient(verify: true);
    [$holder, $claimant, $account] = conflictSetup();
    $summary = disputeService()->open($claimant, '#2PP', str_repeat('mine ', 6));

    $result = app(AccountAttachService::class)->verify($claimant->id, '#2PP', 'good-token');

    expect($result->verified())->toBeTrue();
    $this->assertDatabaseHas('coc_account_disputes', ['id' => $summary->id, 'status' => 'auto_resolved']);
    $this->assertDatabaseHas('coc_accounts', ['user_id' => $claimant->id, 'tag_normalized' => '2PP', 'status' => 'verified']);
});

it('denies the dispute when the holder defends it with a token', function () {
    fakeCocClient(verify: true);
    [$holder, $claimant, $account] = conflictSetup();
    $summary = disputeService()->open($claimant, '#2PP', str_repeat('mine ', 6));

    app(AccountAttachService::class)->verify($holder->id, '#2PP', 'good-token');

    $this->assertDatabaseHas('coc_account_disputes', ['id' => $summary->id, 'status' => 'resolved_denied']);
    expect($account->fresh()->status)->toBe(CocAccountStatus::Verified)
        ->and((int) $account->fresh()->user_id)->toBe($holder->id);
});

it('withdraws a dispute and returns the tag to the holder', function () {
    [$holder, $claimant, $account] = conflictSetup();
    $summary = disputeService()->open($claimant, '#2PP', str_repeat('mine ', 6));

    disputeService()->withdraw($claimant->id, $summary->id);

    expect($account->fresh()->status)->toBe(CocAccountStatus::Verified);
    $this->assertDatabaseHas('coc_account_disputes', ['id' => $summary->id, 'status' => 'withdrawn']);
});

it('rejects a decision on an already-resolved dispute', function () {
    [$holder, $claimant] = conflictSetup();
    $admin = User::factory()->admin()->create();
    $summary = disputeService()->open($claimant, '#2PP', str_repeat('mine ', 6));
    resolutionService()->deny($admin->id, $summary->id, 'first decision stands');

    try {
        resolutionService()->transfer($admin->id, $summary->id, 'changing my mind');
        $this->fail('expected DisputeException');
    } catch (DisputeException $e) {
        expect($e->error)->toBe(DisputeError::NotResolvable);
    }
});
