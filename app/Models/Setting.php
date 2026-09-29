<?php

namespace App\Models;

class Setting extends Record
{
    protected $table = 'settings';

    public static function read(string $key, ?string $fallback = null): ?string
    {
        return cache()->remember(
            "setting:{$key}",
            now()->addMinutes(10),
            fn () => static::where('key', $key)->value('value'),
        ) ?? $fallback;
    }
}
