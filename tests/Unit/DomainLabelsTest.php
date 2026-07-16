<?php

namespace Tests\Unit;

use App\Support\DomainLabels;
use PHPUnit\Framework\TestCase;

class DomainLabelsTest extends TestCase
{
    public function test_it_centralizes_spanish_domain_labels(): void
    {
        $this->assertSame('Borrador', DomainLabels::quotationStatus('draft'));
        $this->assertSame('Aprobada', DomainLabels::quotationStatus('approved'));
        $this->assertSame('Prospecto', DomainLabels::clientType('prospect'));
        $this->assertSame('Anterior', DomainLabels::clientType('past'));
        $this->assertSame('Cliente', DomainLabels::role('cliente'));
    }

    public function test_it_centralizes_event_badge_classes(): void
    {
        $this->assertSame('bg-green-100 text-green-800', DomainLabels::eventStatusClasses('confirmed'));
        $this->assertSame('bg-gray-100 text-gray-800', DomainLabels::eventStatusClasses('unknown'));
    }
}
