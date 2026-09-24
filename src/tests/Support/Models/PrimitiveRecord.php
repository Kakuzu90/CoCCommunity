<?php

namespace Tests\Support\Models;

use App\Support\Casts\PlayerTagCast;
use App\Support\Casts\ThLevelCast;
use Illuminate\Database\Eloquent\Model;

class PrimitiveRecord extends Model
{
    public $timestamps = false;

    protected $fillable = ['tag', 'th_level'];

    protected function casts(): array
    {
        return ['tag' => PlayerTagCast::class, 'th_level' => ThLevelCast::class];
    }
}
