<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class FinancialBalanceCalculator
{
    public function forEvent(Event $event): array
    {
        $event->loadMissing(['transactions', 'quotations']);

        $transactions = $event->transactions
            ->sortBy(fn (Transaction $transaction) => $transaction->transaction_date->format('Y-m-d').str_pad((string) $transaction->id, 12, '0', STR_PAD_LEFT))
            ->values();

        $paid = $transactions->where('status', Transaction::STATUS_PAID);
        $approvedQuotationTotal = $this->sum($event->quotations->where('status', 'approved')->pluck('total'));
        $paidIncome = $this->sum($paid->where('type', Transaction::TYPE_INCOME)->pluck('amount'));
        $paidExpenses = $this->sum($paid->where('type', Transaction::TYPE_EXPENSE)->pluck('amount'));
        $pendingReceivable = bccomp($approvedQuotationTotal, $paidIncome, 2) === 1
            ? bcsub($approvedQuotationTotal, $paidIncome, 2)
            : '0.00';
        $overpayment = bccomp($paidIncome, $approvedQuotationTotal, 2) === 1
            ? bcsub($paidIncome, $approvedQuotationTotal, 2)
            : '0.00';
        $cashBalance = bcsub($paidIncome, $paidExpenses, 2);

        return [
            'approved_quotation_total' => $approvedQuotationTotal,
            'paid_income' => $paidIncome,
            'paid_expenses' => $paidExpenses,
            'pending_receivable' => $pendingReceivable,
            'overpayment' => $overpayment,
            'cash_balance' => $cashBalance,
            'transactions' => $this->withRunningBalance($transactions),

            // Alias temporales para consumidores existentes durante el lote.
            'total' => $approvedQuotationTotal,
            'expenses' => $paidExpenses,
            'pending_income' => $pendingReceivable,
            'pending_balance' => $pendingReceivable,
            'balance' => $cashBalance,
        ];
    }

    public function forEvents(Collection $events): Collection
    {
        return $events->mapWithKeys(
            fn (Event $event): array => [$event->getKey() => $this->forEvent($event)],
        );
    }

    public function totalsForEvents(Collection $events): array
    {
        $keys = [
            'approved_quotation_total',
            'paid_income',
            'paid_expenses',
            'pending_receivable',
            'overpayment',
            'cash_balance',
        ];
        $totals = array_fill_keys($keys, '0.00');

        foreach ($this->forEvents($events) as $balance) {
            foreach ($keys as $key) {
                $totals[$key] = bcadd($totals[$key], $balance[$key], 2);
            }
        }

        return $totals;
    }

    public function withRunningBalance(Collection $transactions): Collection
    {
        $runningBalance = '0.00';

        return $transactions->map(function (Transaction $transaction) use (&$runningBalance): array {
            if ($transaction->status === Transaction::STATUS_PAID) {
                $runningBalance = $transaction->type === Transaction::TYPE_EXPENSE
                    ? bcsub($runningBalance, (string) $transaction->amount, 2)
                    : bcadd($runningBalance, (string) $transaction->amount, 2);
            }

            return [
                'transaction' => $transaction,
                'running_balance' => $runningBalance,
            ];
        });
    }

    private function sum(iterable $amounts): string
    {
        $total = '0.00';

        foreach ($amounts as $amount) {
            $total = bcadd($total, (string) $amount, 2);
        }

        return $total;
    }
}
