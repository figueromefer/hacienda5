<?php

namespace Tests\Unit;

use App\Support\SpanishDate;
use PHPUnit\Framework\TestCase;

class SpanishDateTest extends TestCase
{
    public function test_it_formats_legal_dates_with_explicit_spanish_months(): void
    {
        $this->assertSame('09 DE ABRIL DE 2026', SpanishDate::legal('2026-04-09'));
        $this->assertSame('14 DE JULIO DE 2026', SpanishDate::legal('July 14, 2026'));
        $this->assertStringNotContainsString('July', SpanishDate::legal('July 14, 2026'));
    }
}
