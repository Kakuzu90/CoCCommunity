<?php

namespace App\Domain\Users\Models;

use App\Domain\Users\Enums\ProfileVisibility;
use Illuminate\Database\Eloquent\Model;

/** @property int $user_id
 * @property ProfileVisibility $profile_visibility
 * @property bool $show_coc_accounts
 * @property bool $show_clan
 * @property bool $show_activity
 * @property bool $allow_recruitment_contact
 * @property bool $allow_marketplace_contact
 * @property bool $searchable
 */
class PrivacySetting extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'int';

    /** @var list<string> */
    protected $fillable = [
        'profile_visibility', 'show_coc_accounts', 'show_clan', 'show_activity',
        'allow_recruitment_contact', 'allow_marketplace_contact', 'searchable',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'profile_visibility' => ProfileVisibility::class,
            'show_coc_accounts' => 'boolean',
            'show_clan' => 'boolean',
            'show_activity' => 'boolean',
            'allow_recruitment_contact' => 'boolean',
            'allow_marketplace_contact' => 'boolean',
            'searchable' => 'boolean',
        ];
    }
}
