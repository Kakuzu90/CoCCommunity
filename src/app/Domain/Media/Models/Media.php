<?php

namespace App\Domain\Media\Models;

use App\Domain\Media\Enums\MediaCollection;
use App\Domain\Media\Enums\MediaKind;
use App\Domain\Media\Enums\MediaStatus;
use App\Domain\Media\Enums\MediaVisibility;
use Carbon\CarbonInterface;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Internal to the Media module. Other modules read media through the module's
 * services/DTOs, never this model directly (specs/19 §2).
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property string|null $attachable_type
 * @property int|null $attachable_id
 * @property MediaCollection $collection
 * @property MediaKind $kind
 * @property string $disk
 * @property string $path
 * @property string $original_filename
 * @property string $mime_type
 * @property string $extension
 * @property int $size_bytes
 * @property int|null $width
 * @property int|null $height
 * @property string|null $duration_seconds
 * @property string|null $checksum_sha256
 * @property MediaStatus $status
 * @property string|null $failure_reason
 * @property MediaVisibility $visibility
 * @property int $position
 * @property CarbonInterface|null $processed_at
 * @property CarbonInterface|null $expires_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Collection<int, MediaVariant> $variants
 */
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'media';

    // `user_id` and `status` are set explicitly by the pipeline, never mass-assigned.
    protected $fillable = [
        'ulid',
        'attachable_type',
        'attachable_id',
        'collection',
        'kind',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'extension',
        'size_bytes',
        'width',
        'height',
        'duration_seconds',
        'checksum_sha256',
        'failure_reason',
        'visibility',
        'position',
        'processed_at',
        'expires_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'collection' => MediaCollection::class,
            'kind' => MediaKind::class,
            'status' => MediaStatus::class,
            'visibility' => MediaVisibility::class,
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'position' => 'integer',
            'duration_seconds' => 'decimal:2',
            'processed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @return HasMany<MediaVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(MediaVariant::class);
    }

    protected static function newFactory(): MediaFactory
    {
        return MediaFactory::new();
    }
}
