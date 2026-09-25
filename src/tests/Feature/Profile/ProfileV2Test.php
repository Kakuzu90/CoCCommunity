<?php

use App\Domain\Auth\Models\User;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\Users\Models\PrivacySetting;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function profileOwner(int $verifiedCount = 1): User
{
    $user = User::factory()->create();
    event(new Registered($user));
    $user->forceFill(['verified_accounts_count' => $verifiedCount])->save();

    return $user;
}

function profileAccount(User $owner, string $ign, string $tag, array $attributes = []): CocAccount
{
    $account = new CocAccount(['ign' => $ign, 'th_level' => 15, 'trophies' => 4000, 'war_stars' => $attributes['war_stars'] ?? 100, 'xp_level' => 200]);
    $account->forceFill([
        'ulid' => (string) Str::ulid(), 'user_id' => $owner->id, 'tag' => $tag, 'tag_normalized' => ltrim($tag, '#'),
        'status' => ($attributes['status'] ?? CocAccountStatus::Verified)->value, 'verified_at' => now(),
        'verification_method' => 'api_token', 'is_featured' => $attributes['featured'] ?? false,
        'clan_tag' => $attributes['clan_tag'] ?? null, 'api_synced_at' => now(),
    ])->save();

    return $account;
}

it('renders the cover summary, featured hero card and remaining accounts', function () {
    $owner = profileOwner(2);
    profileAccount($owner, 'Main Village', '#2PP', ['featured' => true, 'war_stars' => 900]);
    profileAccount($owner, 'Farm Account', '#9VU', ['war_stars' => 37]);

    $this->get(route('profile.show', $owner->username))->assertOk()
        ->assertSee('Verified player')
        ->assertSee('Owns at least one Clash of Clans account')
        ->assertSeeInOrder(['profile-cover__summary', 'Highest Town Hall', 'Verified accounts', 'War stars, all accounts', '937', 'profile-featured', 'Main Village', 'Other accounts', 'Farm Account'], false)
        ->assertSee('player-card--hero', false)
        ->assertDontSee('role="tablist"', false)
        ->assertDontSee('Bases published')
        ->assertDontSee('Manage accounts');
});

it('shows base stats and linkable tabs once base publishing is on', function () {
    config(['features.base_publishing' => true]);
    $owner = profileOwner();
    profileAccount($owner, 'Main Village', '#2PP', ['featured' => true]);

    $this->get(route('profile.show', $owner->username))->assertOk()
        ->assertSee('Bases published')->assertSee('Base copies')
        ->assertSee('role="tablist"', false)
        ->assertSee('linkable: true', false);
});

it('shows accounts under review to other viewers but leaves them out of the verified summary', function () {
    $owner = profileOwner();
    profileAccount($owner, 'Main Village', '#2PP', ['featured' => true, 'war_stars' => 900]);
    profileAccount($owner, 'Contested', '#9VU', ['status' => CocAccountStatus::Disputed, 'war_stars' => 50]);
    profileAccount($owner, 'Unproven', '#8QQ', ['status' => CocAccountStatus::Unverified]);

    $this->get(route('profile.show', $owner->username))->assertOk()
        ->assertSee('Contested')->assertSee('Under review')
        ->assertDontSee('Unproven')
        ->assertSee('Verified account')->assertDontSee('Verified accounts')
        ->assertDontSee('War stars, all accounts');
});

it('names the person once for screen readers in the cover', function () {
    $owner = profileOwner();

    $this->get(route('profile.show', $owner->username))->assertOk()
        ->assertDontSee('aria-label="'.$owner->username.', verified"', false);
});

it('says the clan is not shared when the owner hides it', function () {
    $owner = profileOwner();
    profileAccount($owner, 'Main Village', '#2PP', ['featured' => true, 'clan_tag' => '#CLAN1']);
    PrivacySetting::where('user_id', $owner->id)->update(['show_clan' => false]);

    $this->get(route('profile.show', $owner->username))->assertOk()
        ->assertSee('Clan not shared')->assertDontSee('#CLAN1');
    $this->actingAs($owner)->get(route('profile.show', $owner->username))->assertOk()->assertSee('#CLAN1');
});

it('keeps the verified badge when accounts are hidden', function () {
    $owner = profileOwner();
    profileAccount($owner, 'Main Village', '#2PP', ['featured' => true]);
    PrivacySetting::where('user_id', $owner->id)->update(['show_coc_accounts' => false]);

    $this->get(route('profile.show', $owner->username))->assertOk()
        ->assertSee('Verified player')->assertDontSee('Main Village')->assertSee('No public accounts');
});

it('gives the owner account management and an honest empty state', function () {
    $owner = profileOwner(0);

    $this->actingAs($owner)->get(route('profile.show', $owner->username))->assertOk()
        ->assertSee('No accounts attached yet')->assertSee('Attach an account')
        ->assertSee('Edit profile')->assertDontSee('Verified player');
});

it('loads the profile in the same number of queries for one account or six', function () {
    $count = function (User $owner): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('profile.show', $owner->username))->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queries;
    };

    $one = profileOwner();
    profileAccount($one, 'Solo', '#2PP', ['featured' => true]);

    $six = profileOwner(6);
    foreach (['#9VU', '#8QQ', '#7RR', '#6SS', '#5TT', '#4YY'] as $i => $tag) {
        profileAccount($six, 'Alt '.$i, $tag, ['featured' => $i === 0]);
    }

    expect($count($six))->toBe($count($one));
});
