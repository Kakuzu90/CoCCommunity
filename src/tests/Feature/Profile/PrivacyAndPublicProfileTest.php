<?php

use App\Domain\Auth\Models\User;
use App\Domain\Users\Models\PrivacySetting;
use App\Domain\Users\Models\Profile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('provisions privacy defaults on registration', function () {
    $user = User::factory()->create();
    event(new Registered($user));

    $setting = PrivacySetting::findOrFail($user->id);
    expect($setting->profile_visibility->value)->toBe('public')
        ->and($setting->show_coc_accounts)->toBeTrue()
        ->and($setting->allow_recruitment_contact)->toBeFalse();
});

it('requires an active verified owner to edit privacy', function () {
    $this->get(route('settings.privacy.edit'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->unverified()->create())->get(route('settings.privacy.edit'))
        ->assertRedirect(route('verification.notice'));
    $this->actingAs(User::factory()->suspended()->create())->get(route('settings.privacy.edit'))
        ->assertForbidden();
});

it('saves validated privacy settings for the signed-in user only', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($owner)->put(route('settings.privacy.update'), [
        'profile_visibility' => 'members',
        'show_coc_accounts' => '0', 'show_clan' => '0', 'show_activity' => '0',
        'allow_recruitment_contact' => '1', 'allow_marketplace_contact' => '0',
        'searchable' => '0', 'user_id' => $other->id,
    ])->assertRedirect(route('settings.privacy.edit'));

    $setting = PrivacySetting::findOrFail($owner->id);
    expect($setting->profile_visibility->value)->toBe('members')
        ->and($setting->show_coc_accounts)->toBeFalse()
        ->and($setting->allow_recruitment_contact)->toBeTrue()
        ->and(PrivacySetting::find($other->id))->toBeNull();
});

it('rejects invalid visibility and boolean values', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->put(route('settings.privacy.update'), [
        'profile_visibility' => 'staff',
        'show_coc_accounts' => 'yes',
    ])->assertSessionHasErrors(['profile_visibility', 'show_coc_accounts', 'show_clan']);
});

it('shows a public profile without private auth data and escapes its bio', function () {
    $user = User::factory()->create();
    event(new Registered($user));
    Profile::where('user_id', $user->id)->update(['display_name' => 'Chief', 'bio' => '<script>alert(1)</script>']);

    $this->get(route('profile.show', $user->username))->assertOk()
        ->assertSee('Chief')->assertSee('Member since')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee($user->email);
});

it('keeps a private profile hidden from everyone except its owner', function () {
    $owner = User::factory()->create();
    event(new Registered($owner));
    Profile::where('user_id', $owner->id)->update(['bio' => 'secret biography']);
    PrivacySetting::whereKey($owner->id)->update(['profile_visibility' => 'private']);

    $this->get(route('profile.show', $owner->username))->assertOk()
        ->assertSee('This profile is private')->assertDontSee('secret biography')
        ->assertDontSee($owner->username)->assertSee('noindex, nofollow');

    $this->actingAs(User::factory()->create())->get(route('profile.show', $owner->username))
        ->assertSee('This profile is private')->assertDontSee('secret biography');

    $this->actingAs($owner)->get(route('profile.show', $owner->username))
        ->assertSee('secret biography');
});

it('limits members-only profiles to signed-in members', function () {
    $owner = User::factory()->create();
    event(new Registered($owner));
    Profile::where('user_id', $owner->id)->update(['bio' => 'member biography']);
    PrivacySetting::whereKey($owner->id)->update(['profile_visibility' => 'members']);

    $this->get(route('profile.show', $owner->username))->assertSee('This profile is for members')
        ->assertDontSee('member biography');
    $this->actingAs(User::factory()->create())->get(route('profile.show', $owner->username))
        ->assertSee('member biography')->assertSee('noindex, nofollow');
});

it('asks crawlers not to index a public profile when search is disabled', function () {
    $user = User::factory()->create();
    event(new Registered($user));
    PrivacySetting::whereKey($user->id)->update(['searchable' => false]);

    $this->get(route('profile.show', $user->username))->assertOk()
        ->assertSee('noindex, nofollow');
});

it('returns 404 for banned and missing users', function () {
    $banned = User::factory()->banned()->create();
    $this->get(route('profile.show', $banned->username))->assertNotFound();
    $this->get(route('profile.show', 'no_such_user'))->assertNotFound();
});
