<?php

namespace App\Support\Casts;

use App\Support\ValueObjects\ThLevel;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

/** @implements CastsAttributes<ThLevel, ThLevel|int|string> */
final class ThLevelCast implements CastsAttributes, SerializesCastableAttributes
{
    /** @param array<string, mixed> $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?ThLevel
    {
        return $value === null ? null : new ThLevel($value);
    }

    /** @param array<string, mixed> $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return $value === null ? null : ($value instanceof ThLevel ? $value : new ThLevel($value))->value;
    }

    /** @param array<string, mixed> $attributes */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return $this->set($model, $key, $value, $attributes);
    }
}
