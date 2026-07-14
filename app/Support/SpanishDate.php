<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class SpanishDate
{
    private const MONTHS = [
        1 => 'ENERO',
        2 => 'FEBRERO',
        3 => 'MARZO',
        4 => 'ABRIL',
        5 => 'MAYO',
        6 => 'JUNIO',
        7 => 'JULIO',
        8 => 'AGOSTO',
        9 => 'SEPTIEMBRE',
        10 => 'OCTUBRE',
        11 => 'NOVIEMBRE',
        12 => 'DICIEMBRE',
    ];

    public static function legal(DateTimeInterface|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        try {
            $parsed = $date instanceof DateTimeInterface
                ? CarbonImmutable::instance($date)
                : CarbonImmutable::parse($date, 'America/Mexico_City');
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('La fecha proporcionada no es válida.', previous: $exception);
        }

        return sprintf(
            '%02d DE %s DE %04d',
            $parsed->day,
            self::MONTHS[$parsed->month],
            $parsed->year,
        );
    }
}
