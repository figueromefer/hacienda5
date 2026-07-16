<?php

namespace Tests\Unit;

use App\Support\SearchTerm;
use PHPUnit\Framework\TestCase;

class SearchTermTest extends TestCase
{
    public function test_it_trims_terms_and_escapes_like_wildcards(): void
    {
        $this->assertSame('cliente', SearchTerm::clean('  cliente  '));
        $this->assertSame('%50\\%\\_anticipo%', SearchTerm::like(' 50%_anticipo '));
    }
}
