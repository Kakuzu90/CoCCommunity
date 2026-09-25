<?php

use App\Domain\Auth\Models\User;
use App\Domain\Media\Enums\MediaCollection;
use App\Domain\Media\Models\Media;
use App\Domain\Media\Models\MediaVariant;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Models\CocAccountSnapshot;
use App\Domain\PlayerAccounts\Models\SyncState;
use App\Domain\Users\Models\PrivacySetting;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function detailAccount(User $owner, string $ign = 'Night Chief'): CocAccount
{
    $account = new CocAccount([
        'ign' => $ign, 'th_level' => 16, 'trophies' => 5100, 'war_stars' => 900,
        'xp_level' => 215, 'heroes' => [['name' => 'Archer Queen', 'level' => 95, 'maxLevel' => 95]],
        'troops' => [['name' => 'New Unknown Troop', 'level' => 3, 'maxLevel' => 6]],
    ]);
    $account->forceFill([
        'ulid' => (string) Str::ulid(), 'user_id' => $owner->id, 'tag' => '#2PP0LJQ',
        'tag_normalized' => '2PP0LJQ', 'status' => CocAccountStatus::Verified->value,
        'verified_at' => now(), 'verification_method' => 'api_token', 'api_synced_at' => now(),
    ])->save();

    return $account;
}

it('renders a public card and account progression with snapshot changes', function () {
    $owner = User::factory()->create();
    event(new Registered($owner));
    $account = detailAccount($owner);
    CocAccountSnapshot::query()->create([
        'coc_account_id' => $account->id, 'captured_at' => now()->subDay(), 'th_level' => 16,
        'xp_level' => 214, 'trophies' => 5000, 'best_trophies' => 5000,
        'war_stars' => 895, 'attack_wins' => 0, 'defense_wins' => 0, 'donations' => 0,
        'heroes' => [], 'troops' => [], 'spells' => [], 'hero_equipment' => [], 'source' => 'verification',
    ]);
    CocAccountSnapshot::query()->create([
        'coc_account_id' => $account->id, 'captured_at' => now(), 'th_level' => 16,
        'xp_level' => 215, 'trophies' => 5100, 'best_trophies' => 5100,
        'war_stars' => 900, 'attack_wins' => 0, 'defense_wins' => 0, 'donations' => 0,
        'heroes' => [], 'troops' => [], 'spells' => [], 'hero_equipment' => [], 'source' => 'scheduled',
    ]);

    $this->get(route('profile.show', $owner->username))->assertOk()->assertSee('Night Chief');
    $this->get(route('accounts.show', $account->ulid))->assertOk()
        ->assertSee('Archer Queen')->assertSee('New Unknown Troop')
        ->assertSee('Maxed')->assertSee('+100 since last update')
        ->assertDontSee('Refresh game data');
});

it('hides private, members-only, unverified and account-hidden records from public URLs', function () {
    $owner = User::factory()->create();
    event(new Registered($owner));
    $account = detailAccount($owner);
    PrivacySetting::where('user_id', $owner->id)->update(['profile_visibility' => 'private']);
    $this->get(route('accounts.show', $account->ulid))->assertNotFound();

    PrivacySetting::where('user_id', $owner->id)->update(['profile_visibility' => 'members']);
    $this->get(route('accounts.show', $account->ulid))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('accounts.show', $account->ulid))->assertOk();

    PrivacySetting::where('user_id', $owner->id)->update(['profile_visibility' => 'public', 'show_coc_accounts' => false]);
    $this->get(route('accounts.show', $account->ulid))->assertNotFound();
    $this->get(route('profile.show', $owner->username))->assertDontSee('Night Chief');

    PrivacySetting::where('user_id', $owner->id)->update(['show_coc_accounts' => true]);
    $account->forceFill(['status' => CocAccountStatus::Unverified->value])->save();
    $this->get(route('accounts.show', $account->ulid))->assertNotFound();
    $this->actingAs($owner)->get(route('accounts.show', $account->ulid))->assertOk()
        ->assertSee('Unverified')->assertSee('Refresh game data');
});

it('keeps saved progression visible during a stale sync and respects clan privacy', function () {
    $owner = User::factory()->create();
    event(new Registered($owner));
    $account = detailAccount($owner);
    $account->forceFill(['clan_tag' => '#HIDDEN', 'clan_role' => 'leader'])->save();
    PrivacySetting::where('user_id', $owner->id)->update(['show_clan' => false]);
    SyncState::query()->create([
        'resource_type' => 'coc_account', 'resource_id' => $account->id,
        'tier' => 'cold', 'stale' => false, 'consecutive_failures' => 1,
    ]);

    $this->get(route('accounts.show', $account->ulid))->assertOk()
        ->assertSee('Game data is temporarily unavailable')
        ->assertSee('Archer Queen')->assertDontSee('#HIDDEN');
    $this->actingAs($owner)->get(route('accounts.show', $account->ulid))->assertSee('#HIDDEN');
});

it('scopes image attachment and removal to the account owner and enforces five images', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $account = detailAccount($owner);
    $media = Media::factory()->forUser($owner)->ready()->create([
        'collection' => MediaCollection::AccountImage->value, 'expires_at' => now()->addDay(),
    ]);
    MediaVariant::create([
        'media_id' => $media->id, 'variant' => 'card',
        'path' => "public/account_image/{$media->ulid}/card.webp",
        'width' => 800, 'height' => 600, 'size_bytes' => 900, 'mime_type' => 'image/webp',
    ]);

    $url = route('accounts.images.store', $account->ulid);
    $this->actingAs($other)->post($url, ['media_ulid' => $media->ulid])->assertNotFound();
    $this->actingAs($owner)->post($url, ['media_ulid' => $media->ulid])->assertRedirect(route('accounts.show', $account->ulid));
    expect($account->fresh()->images_count)->toBe(1);

    $this->post($url, ['media_ulid' => $media->ulid])->assertSessionHasErrors('media');
    $account->forceFill(['images_count' => 5])->save();
    $next = Media::factory()->forUser($owner)->ready()->create([
        'collection' => MediaCollection::AccountImage->value, 'expires_at' => now()->addDay(),
    ]);
    $this->post($url, ['media_ulid' => $next->ulid])->assertSessionHasErrors('media_ulid');

    $this->get(route('accounts.show', $account->ulid))->assertSee("public/account_image/{$media->ulid}/card.webp");

    $this->actingAs($other)->delete(route('accounts.images.destroy', [$account->ulid, $media->ulid]))->assertNotFound();
    $this->actingAs($owner)->delete(route('accounts.images.destroy', [$account->ulid, $media->ulid]))->assertRedirect();
    expect($media->fresh()->attachable_id)->toBeNull();
});

it('lets the new holder remove an image after account ownership changes', function () {
    $original = User::factory()->create();
    $newHolder = User::factory()->create();
    $account = detailAccount($original);
    $media = Media::factory()->forUser($original)->ready()->create([
        'collection' => MediaCollection::AccountImage->value, 'expires_at' => now()->addDay(),
    ]);
    $this->actingAs($original)->post(route('accounts.images.store', $account->ulid), ['media_ulid' => $media->ulid])->assertRedirect();

    $account->forceFill(['user_id' => $newHolder->id])->save();
    $url = route('accounts.images.destroy', [$account->ulid, $media->ulid]);
    $this->delete($url)->assertNotFound();
    $this->actingAs($newHolder)->delete($url)->assertRedirect();
    expect($media->fresh()->attachable_id)->toBeNull();
});
