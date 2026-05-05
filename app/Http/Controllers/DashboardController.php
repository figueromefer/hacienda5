<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\Quotation;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $income = Transaction::where('type', Transaction::TYPE_INCOME)->where('status', 'paid')->sum('amount');
        $expenses = Transaction::where('type', Transaction::TYPE_EXPENSE)->where('status', 'paid')->sum('amount');
        $balance = $income - $expenses;
        $pendingIncome = Transaction::where('type', Transaction::TYPE_INCOME)->where('status', 'pending')->sum('amount');

        $monthly = Transaction::selectRaw("strftime('%Y-%m', transaction_date) as month")
            ->selectRaw("SUM(CASE WHEN type = 'income' AND status = 'paid' THEN amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type = 'expense' AND status = 'paid' THEN amount ELSE 0 END) as expenses")
            ->groupBy('month')
            ->orderBy('month')
            ->take(6)
            ->get();

        return view('dashboard', [
            'clientsCount' => Client::count(),
            'eventsCount' => Event::count(),
            'income' => $income,
            'expenses' => $expenses,
            'balance' => $balance,
            'pendingIncome' => $pendingIncome,
            'draftQuotations' => Quotation::where('status', 'draft')->count(),
            'nextEvents' => Event::with('client')->orderBy('event_date')->take(5)->get(),
            'monthly' => $monthly,
        ]);
    }
}
