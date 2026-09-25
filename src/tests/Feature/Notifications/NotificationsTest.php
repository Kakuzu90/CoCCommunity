<?php

use App\Domain\Auth\Enums\UserStatus;
use App\Domain\Auth\Events\AccountAnonymized;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Notifications\ExistingAccountNotification;
use App\Domain\Auth\Notifications\NewEmailChangedNotification;
use App\Domain\Auth\Notifications\OldEmailChangedNotification;
use App\Domain\Auth\Notifications\ResetPasswordNotification;
use App\Domain\Auth\Notifications\VerifyEmailNotification;
use App\Domain\Auth\Services\AccountAuthService;
use App\Domain\Auth\Services\CredentialService;
use App\Domain\Notifications\Channels\InboxChannel;
use App\Domain\Notifications\Enums\NoticeKind;
use App\Domain\Notifications\Events\NoticeRequested;
use App\Domain\Notifications\Listeners\SendNotice;
use App\Domain\Notifications\Notifications\AccountNotice;
use App\Domain\Notifications\Services\NotificationReadModel;
use App\Domain\Notifications\Services\Notifier;
use App\Domain\Notifications\Services\PruneNotifications;
use App\Livewire\Components\NotificationBell;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function inboxNotice(User $user, NoticeKind $kind = NoticeKind::PasswordChanged): string
{
    $id = (string) Str::uuid();
    app(InboxChannel::class)->send($user, new AccountNotice($kind, $id));

    return $id;
}

it('requires authentication and scopes inbox, preview and reads to the owner', function () {
    $this->get('/notifications')->assertRedirect('/login');
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $id = inboxNotice($other, NoticeKind::Banned);
    $this->actingAs($owner)->get('/notifications')->assertOk()->assertDontSee('Your account is banned');
    $this->post(route('notifications.read', $id))->assertNotFound();
    expect(DB::table('notifications')->where('id', $id)->value('read_at'))->toBeNull();
    Livewire::actingAs($owner)->test(NotificationBell::class)->assertDontSee('Your account is banned');
    $admin = User::factory()->superAdmin()->create();
    $this->actingAs($admin)->post(route('notifications.read', $id))->assertNotFound();
});

it('marks one and all as read idempotently and invalidates cached counts', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $inbox = app(NotificationReadModel::class);
    expect($inbox->unread($user->id))->toBe(0);
    $id = inboxNotice($user);
    inboxNotice($user);
    $foreign = inboxNotice($other);
    expect($inbox->unread($user->id))->toBe(2);
    $this->actingAs($user)->post(route('notifications.read', $id))->assertRedirect(route('settings.sessions.index'));
    expect($inbox->unread($user->id))->toBe(1);
    $readAt = DB::table('notifications')->where('id', $id)->value('read_at');
    $this->travel(1)->minute();
    $this->post(route('notifications.read', $id))->assertRedirect();
    expect(DB::table('notifications')->where('id', $id)->value('read_at'))->toBe($readAt);
    $this->post(route('notifications.read-all'))->assertRedirect('/notifications');
    expect($inbox->unread($user->id))->toBe(0)
        ->and(DB::table('notifications')->where('id', $foreign)->value('read_at'))->toBeNull();
});

it('paginates and filters the inbox and limits the bell to ten recent rows', function () {
    $user = User::factory()->create();
    for ($i = 0; $i < 21; $i++) {
        inboxNotice($user);
    }
    inboxNotice($user, NoticeKind::Warning);
    $inbox = app(NotificationReadModel::class);
    expect($inbox->paginate($user->id)->count())->toBe(20)
        ->and($inbox->recent($user->id))->toHaveCount(10)
        ->and($inbox->paginate($user->id, 'moderation')->total())->toBe(1);
    $this->actingAs($user)->get('/notifications?category=moderation')->assertOk()->assertSee('You received an account warning');
    $this->get('/notifications?page=2')->assertOk();
    $this->get('/notifications?category[]=security')->assertSessionHasErrors('category');
    Livewire::actingAs($user)->test(NotificationBell::class)->assertSee('22 unread');
    Cache::put('notif:unread:'.$user->id, 101, 60);
    Livewire::test(NotificationBell::class)->assertSee('99+');
});

it('rejects unsafe targets and handles unknown or removed content without a dead link', function () {
    $user = User::factory()->create();
    $id = inboxNotice($user);
    DB::table('notifications')->where('id', $id)->update([
        'type' => 'removed_target',
        'data' => json_encode(['url' => 'javascript:alert(1)', 'title' => '<script>alert(1)</script>']),
    ]);
    $this->actingAs($user)->get('/notifications')->assertOk()
        ->assertSee('This content is no longer available.')
        ->assertDontSee('javascript:', false)->assertDontSee('<script>alert(1)</script>', false);
    $this->post(route('notifications.read', $id))->assertRedirect('/notifications')
        ->assertSessionHas('status', 'This content is no longer available.');
    $this->post('/notifications/not-a-uuid/read')->assertNotFound();
});

it('allows restricted users to read but refuses suspended users', function () {
    $user = User::factory()->create();
    $id = inboxNotice($user);
    $user->forceFill(['status' => UserStatus::Restricted])->save();
    $this->actingAs($user)->post(route('notifications.read', $id))->assertRedirect();
    $user->forceFill(['status' => UserStatus::Suspended])->save();
    $this->get('/notifications')->assertForbidden();
    $this->post(route('notifications.read-all'))->assertForbidden();
});

it('delivers every v1 catalogue entry through notifications without leaking secrets', function (NoticeKind $kind) {
    Notification::fake();
    $user = User::factory()->create();
    $event = new NoticeRequested($user->id, $kind);
    app(Notifier::class)->send($event);
    Notification::assertSentTo($user, AccountNotice::class, fn (AccountNotice $notice): bool => $notice->kind === $kind && $notice->id === $event->id);
    $notice = new AccountNotice($kind, $event->id);
    expect($notice)->toBeInstanceOf(ShouldQueue::class)
        ->and($notice->viaQueues())->toBe([InboxChannel::class => 'high', 'mail' => 'low'])
        ->and($notice->via($user))->toContain(InboxChannel::class);
    expect(in_array('mail', $notice->via($user), true))->toBe($kind->sendsEmail());
    $html = (string) $notice->toMail($user)->render();
    expect($html)->not->toContain($user->email, $user->password, 'internal_note', 'remember_token');
})->with(NoticeKind::cases());

it('queues listeners after commit and drops requests from rolled-back transactions', function () {
    Queue::fake();
    $user = User::factory()->create();
    DB::beginTransaction();
    event(new NoticeRequested($user->id, NoticeKind::PasswordChanged));
    Queue::assertNothingPushed();
    DB::rollBack();
    Queue::assertNothingPushed();
    event(new NoticeRequested($user->id, NoticeKind::PasswordChanged));
    Queue::assertPushedOn('high', CallQueuedListener::class, fn ($job): bool => $job->class === SendNotice::class);
});

it('writes only one inbox row when a delivery is retried', function () {
    $user = User::factory()->create();
    $notice = new AccountNotice(NoticeKind::PasswordChanged, (string) Str::uuid());
    app(InboxChannel::class)->send($user, $notice);
    app(InboxChannel::class)->send($user, $notice);
    expect(app(NotificationReadModel::class)->unread($user->id))->toBe(1);
});

it('dispatches password changes, resets, verification and new-device sign-ins', function () {
    Event::fake([NoticeRequested::class]);
    $user = User::factory()->create();
    app(CredentialService::class)->changePassword($user, 'a-new-long-passphrase', 'current');
    event(new PasswordReset($user));
    event(new Verified($user));
    app(AccountAuthService::class)->recordLogin($user, null, 'new browser');
    Event::assertDispatched(NoticeRequested::class, 4);
    DB::table('sessions')->insert(['id' => 'known', 'user_id' => $user->id, 'user_agent' => 'known browser', 'payload' => '', 'last_activity' => now()->timestamp]);
    app(AccountAuthService::class)->recordLogin($user, null, 'known browser');
    Event::assertDispatched(NoticeRequested::class, 4);
});

it('queues existing security mails on low after commit', function () {
    foreach ([new VerifyEmailNotification, new ResetPasswordNotification('reset-token'), new OldEmailChangedNotification, new NewEmailChangedNotification, new ExistingAccountNotification] as $notice) {
        expect($notice)->toBeInstanceOf(ShouldQueue::class)
            ->and($notice->viaQueues())->toBe(['mail' => 'low'])
            ->and($notice->afterCommit)->toBeTrue();
    }
});

it('notifies both addresses of an email change without disclosing the other address', function () {
    Notification::fake();
    $user = User::factory()->create();
    $old = $user->email;
    app(CredentialService::class)->changeEmail($user, 'new-address@example.test', 'current');
    Notification::assertSentOnDemand(OldEmailChangedNotification::class, fn ($notice, $channels, $notifiable): bool => $notifiable->routes['mail'] === $old);
    Notification::assertSentOnDemand(NewEmailChangedNotification::class, fn ($notice, $channels, $notifiable): bool => $notifiable->routes['mail'] === 'new-address@example.test');
    Notification::assertSentTo($user, VerifyEmailNotification::class);
    expect((string) (new OldEmailChangedNotification)->toMail($user)->render())->not->toContain('new-address@example.test');
    expect((string) (new NewEmailChangedNotification)->toMail($user)->render())->not->toContain($old);
});

it('sends suspension and ban mail after an authorized sanction and no mail after denial', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $this->actingAs($admin)->post(route('admin.users.sanctions.store', $target->username), [
        'type' => 'suspension', 'reason_code' => 'spam', 'public_reason' => 'Repeated spam', 'duration_days' => 1,
        'internal_note' => 'private investigation details',
    ])->assertRedirect();
    Notification::assertSentTo($target, AccountNotice::class, fn (AccountNotice $notice): bool => $notice->kind === NoticeKind::Suspended);
    $this->delete(route('admin.users.sanctions.destroy', $target->username), ['reason' => 'Appeal upheld'])->assertRedirect();
    Notification::assertSentTo($target, AccountNotice::class, fn (AccountNotice $notice): bool => $notice->kind === NoticeKind::SanctionLifted);
    $this->post(route('admin.users.sanctions.store', $target->username), [
        'type' => 'ban', 'reason_code' => 'spam', 'public_reason' => 'Repeated spam',
    ])->assertRedirect();
    Notification::assertSentTo($target, AccountNotice::class, fn (AccountNotice $notice): bool => $notice->kind === NoticeKind::Banned);
    $this->post(route('admin.users.sanctions.store', $admin->username), [
        'type' => 'ban', 'reason_code' => 'spam', 'public_reason' => 'Repeated spam',
    ])->assertForbidden();
    Notification::assertNotSentTo($admin, AccountNotice::class);
});

it('removes anonymized users notifications and skips late deliveries', function () {
    $user = User::factory()->create();
    inboxNotice($user);
    $user->delete();
    event(new AccountAnonymized($user->id));
    expect(app(NotificationReadModel::class)->unread($user->id))->toBe(0);
    Notification::fake();
    app(Notifier::class)->send(new NoticeRequested($user->id, NoticeKind::PasswordChanged));
    Notification::assertNothingSent();
});

it('expires the unread cache after sixty seconds', function () {
    $user = User::factory()->create();
    inboxNotice($user);
    $inbox = app(NotificationReadModel::class);
    expect($inbox->unread($user->id))->toBe(1);
    DB::table('notifications')->update(['read_at' => now()]);
    expect($inbox->unread($user->id))->toBe(1);
    $this->travel(61)->seconds();
    expect($inbox->unread($user->id))->toBe(0);
});

it('prunes by read state and enforces the cap with read rows removed first', function () {
    config(['notifications.max_rows' => 3]);
    $user = User::factory()->create();
    $oldRead = inboxNotice($user);
    $oldUnread = inboxNotice($user);
    DB::table('notifications')->where('id', $oldRead)->update(['read_at' => now(), 'created_at' => now()->subDays(91)]);
    DB::table('notifications')->where('id', $oldUnread)->update(['created_at' => now()->subDays(181)]);
    expect(app(PruneNotifications::class)->run())->toBe(2);
    $read = inboxNotice($user);
    DB::table('notifications')->where('id', $read)->update(['read_at' => now()]);
    for ($i = 0; $i < 3; $i++) {
        inboxNotice($user);
    }
    expect(DB::table('notifications')->where('id', $read)->exists())->toBeFalse()
        ->and(app(NotificationReadModel::class)->unread($user->id))->toBe(3);
    inboxNotice($user);
    expect(DB::table('notifications')->count())->toBe(3);
    $this->artisan('notifications:prune')->assertSuccessful();
});
