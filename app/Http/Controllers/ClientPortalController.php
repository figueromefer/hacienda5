<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientPortalController extends Controller
{
    public function index(Request $request)
    {
        $client = Client::with([
            'user',
            'events' => fn ($query) => $query->orderBy('event_date', 'desc'),
            'payments' => fn ($query) => $query->orderBy('payment_date', 'desc'),
            'documents' => fn ($query) => $query->latest(),
        ])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return view('client-portal.index', compact('client'));
    }
}