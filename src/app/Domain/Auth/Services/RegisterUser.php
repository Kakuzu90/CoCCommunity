<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\RegistrationData;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Notifications\ExistingAccountNotification;
use Illuminate\Auth\Events\Registered;

/**
 * Creates an account and starts email verification (specs/04 §4). Registration is enumeration-safe:
 * an already-registered email is not created again and does not error — the caller shows the same
 * "check your email" outcome, and the real owner is notified out of band (specs/11).
 */
final class RegisterUser
{
    /** @return array{created: bool} */
    public function register(RegistrationData $data): array
    {
        $existing = User::query()->where('email', mb_strtolower(trim($data->email)))->first();
        if ($existing !== null) {
            $existing->notify(new ExistingAccountNotification);

            return ['created' => false];
        }

        $user = new User;
        $user->username = $data->username;
        $user->email = $data->email;
        $user->password = $data->password; // hashed by the model cast

        $user->save();

        // Fires the framework listener that sends the verification email (our branded notification).
        event(new Registered($user));

        return ['created' => true];
    }
}
