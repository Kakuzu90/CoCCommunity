<?php

namespace App\Domain\Users\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Public presentation of a user (specs/07 `profiles`), 1:1 with `users` by `user_id`. Internal to
 * the Users module; other modules read profiles through the module's services/DTOs (specs/19 §2).
 * No Eloquent relation to the User model is declared — the auth row lives in another module and is
 * referenced only by id.
 *
 * `user_id` and `avatar_media_id` are set explicitly by the service, never mass-assigned.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $display_name
 * @property string|null $bio
 * @property int|null $avatar_media_id
 * @property string|null $country_code
 * @property list<string>|null $languages
 * @property string|null $timezone
 * @property array<string, string>|null $socials
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class Profile extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'display_name',
        'bio',
        'country_code',
        'languages',
        'timezone',
        'socials',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'socials' => 'array',
        ];
    }
}
