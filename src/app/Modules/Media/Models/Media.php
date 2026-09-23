<?php

declare(strict_types=1);

namespace App\Modules\Media\Models;

use App\Modules\Media\Enums\MediaStatus;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['disk', 'path', 'mime', 'size', 'checksum'];

    protected function casts(): array
    {
        return ['status' => MediaStatus::class, 'size' => 'integer'];
    }
}
