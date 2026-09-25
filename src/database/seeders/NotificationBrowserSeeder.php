<?php

namespace Database\Seeders;

use App\Domain\Auth\Models\User;
use App\Domain\Notifications\Channels\InboxChannel;
use App\Domain\Notifications\Enums\NoticeKind;
use App\Domain\Notifications\Notifications\AccountNotice;
use App\Domain\Notifications\Services\NotificationReadModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class NotificationBrowserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \LogicException('Browser fixtures are restricted to local and testing environments.');
        }

        $user = User::query()->firstOrCreate(['email' => 'notification-browser@example.test'], [
            'username' => 'notification_browser_fixture',
            'password' => 'browser-fixture-passphrase',
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        app(NotificationReadModel::class)->owned($user->id)->delete();

        for ($index = 0; $index < 23; $index++) {
            $id = (string) Str::uuid();
            $kind = $index % 2 === 0 ? NoticeKind::PasswordChanged : NoticeKind::Warning;
            app(InboxChannel::class)->send($user, new AccountNotice($kind, $id));
            DB::table('notifications')->where('id', $id)->update(['created_at' => now()->subMinutes($index + 1)]);
        }
    }
}
