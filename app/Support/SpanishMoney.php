<?php

namespace App\Support;

class SpanishMoney
{
    public static function toWords(float $amount): string
    {
        $integer = (int) floor($amount);
        $cents = (int) round(($amount - $integer) * 100);

        return mb_strtoupper(self::numberToWords($integer).' PESOS '.str_pad((string) $cents, 2, '0', STR_PAD_LEFT).'/100 M.N');
    }

    private static function numberToWords(int $number): string
    {
        if ($number === 0) {
            return 'cero';
        }

        $units = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'];
        $tens = ['', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $hundreds = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

        if ($number < 20) {
            return $units[$number];
        }

        if ($number < 30) {
            return $number === 20 ? 'veinte' : 'veinti'.$units[$number - 20];
        }

        if ($number < 100) {
            $ten = intdiv($number, 10);
            $unit = $number % 10;

            return $tens[$ten].($unit ? ' y '.$units[$unit] : '');
        }

        if ($number === 100) {
            return 'cien';
        }

        if ($number < 1000) {
            $hundred = intdiv($number, 100);
            $rest = $number % 100;

            return $hundreds[$hundred].($rest ? ' '.self::numberToWords($rest) : '');
        }

        if ($number < 1000000) {
            $thousands = intdiv($number, 1000);
            $rest = $number % 1000;
            $prefix = $thousands === 1 ? 'mil' : self::numberToWords($thousands).' mil';

            return $prefix.($rest ? ' '.self::numberToWords($rest) : '');
        }

        $millions = intdiv($number, 1000000);
        $rest = $number % 1000000;
        $prefix = $millions === 1 ? 'un millón' : self::numberToWords($millions).' millones';

        return $prefix.($rest ? ' '.self::numberToWords($rest) : '');
    }
}
