<?php

namespace App\Support;

class Phone
{
    public static function normalize(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }
        $value = preg_replace('/[\s()+-]/', '', $value ?? '');
        if (str_starts_with($value, '0')) {
            $value = '62'.substr($value, 1);
        }
        if (str_starts_with($value, '8')) {
            $value = '62'.$value;
        }

        return $value;
    }
}
