<?php

namespace App\Support;

class MoneyNormalizer
{
    public static function normalize(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value) && ! is_int($value)) {
            return $value;
        }

        $normalized = preg_replace('/[\\s\\x{00A0}]+/u', '', (string) $value);
        $normalized = str_replace(['$', ','], '', $normalized ?? '');

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/^(?<sign>[+-]?)(?<integer>\\d*)(?:\\.(?<fraction>\\d*))?$/', $normalized, $matches) !== 1
            || ($matches['integer'] === '' && ($matches['fraction'] ?? '') === '')) {
            return $value;
        }

        $integer = ltrim($matches['integer'], '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = $matches['fraction'] ?? '';
        $decimal = str_contains($normalized, '.') ? '.'.$fraction : '';

        return $matches['sign'].$integer.$decimal;
    }

    public static function normalizeArray(array $input, array $paths): array
    {
        foreach ($paths as $path) {
            self::normalizePath($input, explode('.', $path));
        }

        return $input;
    }

    private static function normalizePath(array &$input, array $segments): void
    {
        $segment = array_shift($segments);

        if ($segment === null) {
            return;
        }

        if ($segment === '*') {
            foreach ($input as &$value) {
                if ($segments === []) {
                    $value = self::normalize($value);
                } elseif (is_array($value)) {
                    self::normalizePath($value, $segments);
                }
            }

            return;
        }

        if (! array_key_exists($segment, $input)) {
            return;
        }

        if ($segments === []) {
            $input[$segment] = self::normalize($input[$segment]);

            return;
        }

        if (is_array($input[$segment])) {
            self::normalizePath($input[$segment], $segments);
        }
    }
}
