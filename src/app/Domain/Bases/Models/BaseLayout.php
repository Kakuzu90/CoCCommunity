<?php

namespace App\Domain\Bases\Models;

use App\Domain\Bases\Enums\BaseCategory;
use App\Domain\Bases\Enums\BaseModerationState;
use App\Domain\Bases\Enums\BaseStatus;
use App\Domain\Bases\Enums\BaseVisibility;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $ulid
 * @property string $slug
 * @property int $user_id
 * @property int|null $coc_account_id
 * @property string $title
 * @property string|null $description
 * @property int $th_level
 * @property BaseCategory $category
 * @property string $base_link
 * @property string $layout_hash
 * @property BaseVisibility $visibility
 * @property BaseStatus $status
 * @property BaseModerationState $moderation_state
 * @property string|null $flagged_reason
 * @property bool $has_video
 * @property CarbonInterface|null $published_at
 */
final class BaseLayout extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'description', 'th_level', 'category', 'base_link', 'layout_hash', 'visibility'];

    protected function casts(): array
    {
        return [
            'category' => BaseCategory::class,
            'visibility' => BaseVisibility::class,
            'status' => BaseStatus::class,
            'moderation_state' => BaseModerationState::class,
            'has_video' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
