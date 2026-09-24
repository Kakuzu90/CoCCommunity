<?php

use App\Domain\Auth\Enums\UserStatus;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Notifications\OldEmailChangedNotification;
use App\Domain\Auth\Notifications\VerifyEmailNotification;
use App\Domain\Auth\Services\AccountDeletionService;
use App\Domain\Auth\Services\TrackedDatabaseSessionHandler;
use App\Domain\Users\Models\Profile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function sessionRow(User $user, string $id): void
{
    DB::table('sessions')->insert([
        'id' => $id, 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla Firefox', 'payload' => 'x', 'last_activity' => now()->timestamp,
        'device_label' => 'Firefox on Windows', 'created_at' => now(),
    ]);
}

it('lists only owned sessions and revokes an owned remote session', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    sessionRow($owner, 'owned-session');
    sessionRow($other, 'foreign-session');

    $this->actingAs($owner)->get(route('settings.sessions.index'))
        ->assertOk()->assertSee('Firefox on Windows')->assertDontSee('foreign-session');
    $this->actingAs($owner)->delete(route('settings.sessions.destroy', 'foreign-session'))->assertNotFound();
    $this->actingAs($owner)->delete(route('settings.sessions.destroy', 'owned-session'))->assertRedirect();
    expect(DB::table('sessions')->where('id', 'owned-session')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'foreign-session')->exists())->toBeTrue();
});

it('revokes other sessions without revoking the current one', function () {
    $user = User::factory()->create();
    sessionRow($user, 'remote');
    $this->actingAs($user)->delete(route('settings.sessions.destroy-others'))->assertRedirect();
    expect(DB::table('sessions')->where('id', 'remote')->exists())->toBeFalse();
});

it('signs out everywhere when all sessions are revoked', function () {
    $user = User::factory()->create();
    sessionRow($user, 'remote');
    $this->actingAs($user)->delete(route('settings.sessions.destroy-all'))
        ->assertRedirect(route('login'));
    $this->assertGuest();
    expect(DB::table('sessions')->where('id', 'remote')->exists())->toBeFalse();
});

it('writes device metadata and creation time to database sessions', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->app->instance('request', Request::create('/', 'GET', server: [
        'REMOTE_ADDR' => '192.0.2.10', 'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0) Firefox/120.0',
    ]));
    $handler = new TrackedDatabaseSessionHandler(DB::connection(), 'sessions', 120, $this->app);
    $handler->write('tracked-session', 'test-payload');

    $row = DB::table('sessions')->where('id', 'tracked-session')->first();
    expect($row->device_label)->toBe('Firefox on Windows')
        ->and($row->ip_hash)->toHaveLength(64)
        ->and($row->created_at)->not->toBeNull();
});

it('registers the tracked database session handler', function () {
    expect(app('session')->driver('database')->getHandler())->toBeInstanceOf(TrackedDatabaseSessionHandler::class);
});

it('changes password with confirmation and revokes remote sessions', function () {
    $user = User::factory()->create(['password' => Hash::make('old-passphrase')]);
    sessionRow($user, 'remote');

    $this->actingAs($user)->put(route('settings.security.password.update'), [
        'current_password' => 'wrong', 'password' => 'new-strong-passphrase',
        'password_confirmation' => 'new-strong-passphrase',
    ])->assertSessionHasErrors('current_password');

    $this->actingAs($user)->put(route('settings.security.password.update'), [
        'current_password' => 'old-passphrase', 'password' => 'new-strong-passphrase',
        'password_confirmation' => 'new-strong-passphrase',
    ])->assertRedirect(route('settings.security.edit'));

    expect(Hash::check('new-strong-passphrase', $user->refresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->where('id', 'remote')->exists())->toBeFalse();
});

it('changes email, requires verification and notifies the old address', function () {
    Notification::fake();
    $user = User::factory()->create(['password' => Hash::make('old-passphrase')]);
    sessionRow($user, 'remote');
    $old = $user->email;

    $this->actingAs($user)->put(route('settings.security.email.update'), [
        'current_password' => 'old-passphrase', 'email' => 'new@example.com',
    ])->assertRedirect(route('verification.notice'));

    expect($user->refresh()->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->toBeNull()
        ->and(DB::table('sessions')->where('id', 'remote')->exists())->toBeFalse();
    Notification::assertSentOnDemand(OldEmailChangedNotification::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $old);
    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('requires a password for deletion and lets a pending account cancel', function () {
    $user = User::factory()->create(['password' => Hash::make('old-passphrase')]);
    event(new Registered($user));
    sessionRow($user, 'remote');

    $this->actingAs($user)->post(route('settings.deletion.request'), ['current_password' => 'wrong'])
        ->assertSessionHasErrors('current_password');
    $this->actingAs($user)->post(route('settings.deletion.request'), ['current_password' => 'old-passphrase'])
        ->assertRedirect(route('login'));

    expect($user->refresh()->status)->toBe(UserStatus::PendingDeletion)
        ->and($user->deletion_requested_at)->not->toBeNull()
        ->and(DB::table('sessions')->where('id', 'remote')->exists())->toBeFalse();
    $this->get(route('profile.show', $user->username))->assertNotFound();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'old-passphrase'])->assertRedirect();
    $this->get(route('settings.deletion.show'))->assertOk()->assertSee('Deletion scheduled');
    $this->delete(route('settings.deletion.cancel'), ['current_password' => 'old-passphrase'])->assertRedirect();
    expect($user->refresh()->status)->toBe(UserStatus::Active)
        ->and($user->deletion_requested_at)->toBeNull();
});

it('anonymizes due accounts once while leaving recent requests intact', function () {
    $due = User::factory()->create();
    $recent = User::factory()->create();
    event(new Registered($due));
    Profile::where('user_id', $due->id)->update(['bio' => 'private bio']);
    $due->forceFill(['status' => UserStatus::PendingDeletion, 'deletion_requested_at' => now()->subDays(31)])->save();
    $recent->forceFill(['status' => UserStatus::PendingDeletion, 'deletion_requested_at' => now()])->save();

    expect(app(AccountDeletionService::class)->anonymizeDue())->toBe(1)
        ->and(app(AccountDeletionService::class)->anonymizeDue())->toBe(0)
        ->and(User::query()->find($due->id))->toBeNull()
        ->and(User::query()->find($recent->id))->not->toBeNull()
        ->and(Profile::where('user_id', $due->id)->firstOrFail()->bio)->toBeNull();
});
