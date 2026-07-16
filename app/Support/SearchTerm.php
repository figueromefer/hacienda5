<?php

namespace App\Support;

class SearchTerm
{
    public static function clean(mixed $value): string
    {
        return trim((string) $value);
    }

    public static function like(mixed $value): string
    {
        return '%'.addcslashes(self::clean($value), '\\%_').'%';
    }
}
