<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\FinancialBalanceCalculator;
use Illuminate\Http\Request;

class ClientPortalController extends Controller
{
    public function index(Request $request, FinancialBalanceCalculator $balanceCalculator)
    {
        $client = Client::with([
            'user',
            'events' => fn ($query) => $query
                ->with([
                    'quotations:id,event_id,status,total',
                    'transactions:id,event_id,type,status,amount,transaction_date',
                ])
                ->orderBy('event_date', 'desc'),
            'payments' => fn ($query) => $query->orderBy('payment_date', 'desc'),
            'documents' => fn ($query) => $query->latest(),
        ])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $eventBalances = $balanceCalculator->forEvents($client->events);

        return view('client-portal.index', compact('client', 'eventBalances'));
    }
}
