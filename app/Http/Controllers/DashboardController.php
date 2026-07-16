<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\Quotation;
use App\Models\Transaction;
use App\Services\FinancialBalanceCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request, FinancialBalanceCalculator $balanceCalculator)
    {
        $period = in_array($request->get('period'), ['6', '12', 'year'], true)
            ? $request->get('period')
            : '6';

        $fromDate = match ($period) {
            '12' => now()->startOfMonth()->subMonths(11),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth()->subMonths(5),
        };

        $income = Transaction::where('type', Transaction::TYPE_INCOME)->where('status', Transaction::STATUS_PAID)->sum('amount');
        $expenses = Transaction::where('type', Transaction::TYPE_EXPENSE)->where('status', Transaction::STATUS_PAID)->sum('amount');
        $balance = $income - $expenses;
        $events = Event::query()
            ->select('id')
            ->with([
                'quotations:id,event_id,status,total',
                'transactions:id,event_id,type,status,amount,transaction_date',
            ])
            ->get();
        $pendingIncome = $balanceCalculator->totalsForEvents($events)['pending_receivable'];

        $driver = DB::connection()->getDriverName();
        $monthExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m', transaction_date)"
            : "DATE_FORMAT(transaction_date, '%Y-%m')";

        $monthly = Transaction::whereDate('transaction_date', '>=', $fromDate)
            ->selectRaw("{$monthExpression} as month")
            ->selectRaw("SUM(CASE WHEN type = 'income' AND status = 'paid' THEN amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type = 'expense' AND status = 'paid' THEN amount ELSE 0 END) as expenses")
            ->groupByRaw($monthExpression)
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $monthsCount = $period === 'year'
            ? now()->month
            : (int) $period;

        $series = collect(range($monthsCount - 1, 0))->reverse()->map(function ($offset) {
            return now()->startOfMonth()->subMonths($offset)->format('Y-m');
        })->values();

        if ($period === 'year') {
            $series = collect(range(1, now()->month))->map(fn ($month) => now()->startOfYear()->month($month)->format('Y-m'));
        }

        $chartLabels = $series->map(fn ($month) => Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y'))->values();
        $chartIncome = $series->map(fn ($month) => (float) optional($monthly->get($month))->income)->values();
        $chartExpenses = $series->map(fn ($month) => (float) optional($monthly->get($month))->expenses)->values();
        $chartBalance = $series->map(function ($month) use ($monthly) {
            $row = $monthly->get($month);

            return (float) optional($row)->income - (float) optional($row)->expenses;
        })->values();

        return view('dashboard', [
            'clientsCount' => Client::count(),
            'eventsCount' => $events->count(),
            'income' => $income,
            'expenses' => $expenses,
            'balance' => $balance,
            'pendingIncome' => $pendingIncome,
            'draftQuotations' => Quotation::where('status', 'draft')->count(),
            'nextEvents' => Event::with('client')->orderBy('event_date')->take(5)->get(),
            'period' => $period,
            'chartLabels' => $chartLabels,
            'chartIncome' => $chartIncome,
            'chartExpenses' => $chartExpenses,
            'chartBalance' => $chartBalance,
        ]);
    }
}
