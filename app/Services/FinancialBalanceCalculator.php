<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class FinancialBalanceCalculator
{
    public function forEvent(Event $event): array
    {
        $transactions = $event->transactions
            ->sortBy(fn (Transaction $transaction) => $transaction->transaction_date->format('Y-m-d').str_pad((string) $transaction->id, 12, '0', STR_PAD_LEFT))
            ->values();

        $paid = $transactions->where('status', 'paid');
        $paidIncome = (float) $paid->where('type', Transaction::TYPE_INCOME)->sum('amount');
        $pendingIncome = (float) $transactions
            ->where('status', 'pending')
            ->where('type', Transaction::TYPE_INCOME)
            ->sum('amount');
        $expenses = (float) $paid->where('type', Transaction::TYPE_EXPENSE)->sum('amount');
        $total = (float) $event->total_amount;

        return [
            'total' => $total,
            'paid_income' => $paidIncome,
            'pending_income' => $pendingIncome,
            'expenses' => $expenses,
            'pending_balance' => $total - $paidIncome,
            'balance' => $paidIncome - $expenses,
            'transactions' => $this->withRunningBalance($transactions),
        ];
    }

    public function withRunningBalance(Collection $transactions): Collection
    {
        $runningBalance = 0.0;

        return $transactions->map(function (Transaction $transaction) use (&$runningBalance): array {
            if ($transaction->status === 'paid') {
                $runningBalance += $transaction->signed_amount;
            }

            return [
                'transaction' => $transaction,
                'running_balance' => $runningBalance,
            ];
        });
    }
}
