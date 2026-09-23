<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Users\Actions\DeleteUser;
use App\Modules\Users\Events\UserDeleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Livewire\Volt\Volt;

function seedCredentials(User $user): void
{
    DB::table('sessions')->insert([
        'id' => 'session-'.$user->id,
        'user_id' => $user->id,
        'payload' => 'test-session',
        'last_activity' => time(),
    ]);
    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => 'test-token',
        'created_at' => now(),
    ]);
}

test('module routes retain guest and authenticated boundaries', function (): void {
    $this->get('/register')->assertOk();
    $this->get('/profile')->assertRedirect(route('login'));
    $this->get('/verify-email')->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create());
    $this->get('/profile')->assertOk();
    $this->get('/register')->assertRedirect(route('dashboard', absolute: false));
});

test('deleting an account revokes only its credentials through the auth listener', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    seedCredentials($owner);
    seedCredentials($other);

    $this->actingAs($owner);
    Volt::test('profile.delete-user-form')->set('password', 'password')
        ->call('deleteUser')->assertHasNoErrors()->assertRedirect('/');

    $this->assertSoftDeleted('users', ['id' => $owner->id]);
    $this->assertDatabaseMissing('sessions', ['user_id' => $owner->id]);
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $owner->email]);
    $this->assertDatabaseHas('sessions', ['user_id' => $other->id]);
    $this->assertDatabaseHas('password_reset_tokens', ['email' => $other->email]);
    $this->assertGuest();
});

test('invalid deletion credentials leave sessions and reset tokens intact', function (): void {
    $user = User::factory()->create();
    seedCredentials($user);

    $this->actingAs($user);
    Volt::test('profile.delete-user-form')->set('password', 'wrong-password')
        ->call('deleteUser')->assertHasErrors('password');

    $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    $this->assertDatabaseHas('sessions', ['user_id' => $user->id]);
    $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
});

test('a failed deletion side effect rolls back the account and credentials together', function (): void {
    $user = User::factory()->create();
    seedCredentials($user);
    Event::listen(UserDeleted::class, function (): void {
        throw new RuntimeException('Deletion failed');
    });

    expect(fn () => app(DeleteUser::class)->handle($user))->toThrow(RuntimeException::class, 'Deletion failed');

    $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    $this->assertDatabaseHas('sessions', ['user_id' => $user->id]);
    $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
});
