<?php

declare(strict_types=1);

namespace App\Modules\Media\Models;

use App\Models\User;
use App\Modules\Media\Enums\MediaKind;
use App\Modules\Media\Enums\MediaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Media extends Model
{
    protected $table = 'media';

    // status and uploader_id are set explicitly by trusted actions (forceCreate),
    // never mass-assigned from request input.
    protected $fillable = [
        'parent_id', 'disk', 'path', 'kind', 'variant', 'mime',
        'size', 'width', 'height', 'checksum',
    ];

    protected function casts(): array
    {
        return [
            'kind' => MediaKind::class,
            'status' => MediaStatus::class,
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function isImage(): bool
    {
        return $this->kind === MediaKind::Image;
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /** @return BelongsTo<Media, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Media, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
