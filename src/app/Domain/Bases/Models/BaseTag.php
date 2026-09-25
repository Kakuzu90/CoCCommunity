<?php

namespace App\Domain\Bases\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_suggested
 * @property bool $is_blocked
 * @property int $usage_count
 */
final class BaseTag extends Model
{
    protected $fillable = ['name', 'slug'];

    protected function casts(): array
    {
        return ['is_suggested' => 'boolean', 'is_blocked' => 'boolean'];
    }
}
