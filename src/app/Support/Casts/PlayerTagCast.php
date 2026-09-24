<?php

namespace App\Support\Casts;

use App\Support\ValueObjects\PlayerTag;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

/** @implements CastsAttributes<PlayerTag, PlayerTag|string> */
final class PlayerTagCast implements CastsAttributes, SerializesCastableAttributes
{
    /** @param array<string, mixed> $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PlayerTag
    {
        return $value === null ? null : new PlayerTag($value);
    }

    /** @param array<string, mixed> $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : ($value instanceof PlayerTag ? $value : new PlayerTag($value))->value;
    }

    /** @param array<string, mixed> $attributes */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $this->set($model, $key, $value, $attributes);
    }
}
