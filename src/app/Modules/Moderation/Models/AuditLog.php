<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_id', 'action', 'subject_type', 'subject_id', 'before', 'after', 'ip',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }
}
