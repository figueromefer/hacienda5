<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Quotation;
use App\Models\Transaction;
use App\Services\FinancialBalanceCalculator;
use Tests\TestCase;

class FinancialBalanceCalculatorCharacterizationTest extends TestCase
{
    public function test_event_balance_uses_approved_quotations_and_only_paid_movements(): void
    {
        $event = new Event(['total_amount' => 9999]);
        $event->setRelation('quotations', collect([
            $this->quotation('approved', 1000),
            $this->quotation('draft', 8000),
        ]));
        $event->setRelation('transactions', collect([
            $this->transaction(1, 'income', 'paid', 400, '2026-07-01'),
            $this->transaction(2, 'income', 'pending', 200, '2026-07-02'),
            $this->transaction(3, 'expense', 'paid', 75, '2026-07-03'),
            $this->transaction(4, 'income', 'cancelled', 300, '2026-07-04'),
        ]));

        $balance = (new FinancialBalanceCalculator)->forEvent($event);

        $this->assertSame('1000.00', $balance['approved_quotation_total']);
        $this->assertSame('400.00', $balance['paid_income']);
        $this->assertSame('75.00', $balance['paid_expenses']);
        $this->assertSame('600.00', $balance['pending_receivable']);
        $this->assertSame('0.00', $balance['overpayment']);
        $this->assertSame('325.00', $balance['cash_balance']);
    }

    public function test_running_balance_is_chronological_and_ignores_non_paid_movements(): void
    {
        $event = new Event;
        $event->setRelation('quotations', collect());
        $event->setRelation('transactions', collect([
            $this->transaction(4, 'income', 'cancelled', 500, '2026-07-04'),
            $this->transaction(2, 'expense', 'paid', 25, '2026-07-02'),
            $this->transaction(1, 'income', 'paid', 100, '2026-07-01'),
            $this->transaction(3, 'income', 'pending', 50, '2026-07-03'),
        ]));

        $balance = (new FinancialBalanceCalculator)->forEvent($event);

        $this->assertSame([1, 2, 3, 4], $balance['transactions']->map(
            fn (array $row): int => $row['transaction']->id,
        )->all());
        $this->assertSame(['100.00', '75.00', '75.00', '75.00'], $balance['transactions']->pluck('running_balance')->all());
    }

    private function quotation(string $status, int $total): Quotation
    {
        return new Quotation(['status' => $status, 'total' => $total]);
    }

    private function transaction(int $id, string $type, string $status, int $amount, string $date): Transaction
    {
        $transaction = new Transaction([
            'type' => $type,
            'scope' => 'event',
            'transaction_date' => $date,
            'amount' => $amount,
            'status' => $status,
        ]);
        $transaction->id = $id;

        return $transaction;
    }
}
