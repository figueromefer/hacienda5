<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Transaction;
use App\Services\FinancialBalanceCalculator;
use Tests\TestCase;

class FinancialBalanceCalculatorCharacterizationTest extends TestCase
{
    public function test_current_event_balance_uses_manual_total_and_only_paid_movements(): void
    {
        $event = new Event(['total_amount' => 1000]);
        $event->setRelation('transactions', collect([
            $this->transaction(1, 'income', 'paid', 400, '2026-07-01'),
            $this->transaction(2, 'income', 'pending', 200, '2026-07-02'),
            $this->transaction(3, 'expense', 'paid', 75, '2026-07-03'),
            $this->transaction(4, 'income', 'cancelled', 300, '2026-07-04'),
        ]));

        $balance = (new FinancialBalanceCalculator)->forEvent($event);

        $this->assertSame(1000.0, $balance['total']);
        $this->assertSame(400.0, $balance['paid_income']);
        $this->assertSame(200.0, $balance['pending_income']);
        $this->assertSame(75.0, $balance['expenses']);
        $this->assertSame(600.0, $balance['pending_balance']);
        $this->assertSame(325.0, $balance['balance']);
    }

    public function test_current_running_balance_is_chronological_and_ignores_non_paid_movements(): void
    {
        $transactions = collect([
            $this->transaction(4, 'income', 'cancelled', 500, '2026-07-04'),
            $this->transaction(2, 'expense', 'paid', 25, '2026-07-02'),
            $this->transaction(1, 'income', 'paid', 100, '2026-07-01'),
            $this->transaction(3, 'income', 'pending', 50, '2026-07-03'),
        ]);
        $event = new Event(['total_amount' => 0]);
        $event->setRelation('transactions', $transactions);

        $balance = (new FinancialBalanceCalculator)->forEvent($event);

        $this->assertSame([1, 2, 3, 4], $balance['transactions']->map(
            fn (array $row): int => $row['transaction']->id,
        )->all());
        $this->assertSame([100.0, 75.0, 75.0, 75.0], $balance['transactions']->pluck('running_balance')->all());
    }

    private function transaction(
        int $id,
        string $type,
        string $status,
        float $amount,
        string $date,
    ): Transaction {
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
