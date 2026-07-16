<?php

namespace Tests\Unit;

use App\Support\MoneyNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyNormalizerTest extends TestCase
{
    #[DataProvider('moneyValues')]
    public function test_it_normalizes_money_for_server_validation(mixed $input, mixed $expected): void
    {
        $this->assertSame($expected, MoneyNormalizer::normalize($input));
    }

    public static function moneyValues(): array
    {
        return [
            'empty' => ['', null],
            'spaces' => ['   ', null],
            'integer' => ['001250', '1250'],
            'decimal' => ['1250.50', '1250.50'],
            'commas' => ['125,430.50', '125430.50'],
            'symbol and spaces' => ['$ 125,430.50', '125430.50'],
            'leading decimal' => ['.50', '0.50'],
            'invalid remains invalid for validation' => ['12x.50', '12x.50'],
        ];
    }

    public function test_it_normalizes_nested_money_fields(): void
    {
        $payload = MoneyNormalizer::normalizeArray([
            'discount' => '$ 1,000.00',
            'items' => [
                ['unit_price' => '$ 250.00', 'description' => 'Renta'],
                ['unit_price' => '1,500.50', 'description' => 'Banquete'],
            ],
        ], ['discount', 'items.*.unit_price']);

        $this->assertSame('1000.00', $payload['discount']);
        $this->assertSame('250.00', $payload['items'][0]['unit_price']);
        $this->assertSame('1500.50', $payload['items'][1]['unit_price']);
        $this->assertSame('Renta', $payload['items'][0]['description']);
    }
}
