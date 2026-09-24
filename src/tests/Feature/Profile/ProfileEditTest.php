<?php

use App\Domain\Auth\Models\User;
use App\Domain\Users\Models\Profile;
use App\Domain\Users\Models\UserStats;
use App\Domain\Users\Services\ProfileService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects a guest to login', function () {
    $this->get(route('settings.profile.edit'))->assertRedirect(route('login'));
});

it('blocks an unverified user from the edit page', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('settings.profile.edit'))
        ->assertRedirect(route('verification.notice'));
});

it('blocks a suspended user from editing', function () {
    $this->actingAs(User::factory()->suspended()->create())
        ->get(route('settings.profile.edit'))
        ->assertForbidden();
});

it('shows the edit page and provisions a profile on first visit', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('settings.profile.edit'))
        ->assertOk()
        ->assertSee('Edit profile')
        ->assertSee('@'.$user->username);

    expect(Profile::where('user_id', $user->id)->exists())->toBeTrue();
});

it('saves valid profile fields and normalises them', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('settings.profile.update'), [
        'display_name' => '  Chief Ruben  ',
        'bio' => 'TH15 rusher, war three-star enjoyer.',
        'country_code' => 'gb',
        'languages' => ['English', 'Bisaya'],
        'timezone' => 'Europe/London',
        'socials' => ['youtube' => 'ChiefRuben', 'twitch' => 'chief_ruben'],
    ])->assertRedirect(route('settings.profile.edit'))->assertSessionHas('status', 'profile-updated');

    $profile = Profile::where('user_id', $user->id)->firstOrFail();
    expect($profile->display_name)->toBe('Chief Ruben')
        ->and($profile->country_code)->toBe('GB')
        ->and($profile->languages)->toBe(['English', 'Bisaya'])
        ->and($profile->socials)->toBe(['youtube' => 'ChiefRuben', 'twitch' => 'chief_ruben']);
});

it('clears optional fields when submitted blank', function () {
    $user = User::factory()->create();
    app(ProfileService::class)->ensure($user->id);
    Profile::where('user_id', $user->id)->update(['display_name' => 'Old', 'country_code' => 'US']);

    $this->actingAs($user)->put(route('settings.profile.update'), [
        'display_name' => '',
        'country_code' => '',
    ])->assertRedirect();

    $profile = Profile::where('user_id', $user->id)->firstOrFail();
    expect($profile->display_name)->toBeNull()->and($profile->country_code)->toBeNull();
});

it('rejects invalid profile input', function (array $payload, string $field) {
    $user = User::factory()->create();

    $this->actingAs($user)->from(route('settings.profile.edit'))
        ->put(route('settings.profile.update'), $payload)
        ->assertSessionHasErrors($field);
})->with([
    'bio too long' => [['bio' => str_repeat('a', 501)], 'bio'],
    'too many languages' => [['languages' => ['English', 'German', 'French', 'Spanish']], 'languages'],
    'bad country' => [['country_code' => 'GBR'], 'country_code'],
    'language too long' => [['languages' => [str_repeat('a', 31)]], 'languages.0'],
    'social with spaces' => [['socials' => ['youtube' => 'has spaces']], 'socials.youtube'],
    'bad timezone' => [['timezone' => 'Mars/Phobos'], 'timezone'],
]);

it('drops unknown social platforms', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('settings.profile.update'), [
        'socials' => ['youtube' => 'keep', 'myspace' => 'drop'],
    ])->assertRedirect();

    expect(Profile::where('user_id', $user->id)->firstOrFail()->socials)
        ->toBe(['youtube' => 'keep']);
});

it('provisions a profile and stats row when a user registers', function () {
    $user = User::factory()->create();

    event(new Registered($user));

    expect(Profile::where('user_id', $user->id)->exists())->toBeTrue()
        ->and(UserStats::whereKey($user->id)->exists())->toBeTrue();
});
