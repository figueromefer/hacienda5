<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Quotation;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard', [
            'clientsCount' => Client::count(),
            'eventsCount' => Event::count(),
            'pendingPayments' => Payment::where('status', 'pending')->sum('amount'),
            'draftQuotations' => Quotation::where('status', 'draft')->count(),
            'nextEvents' => Event::with('client')->orderBy('event_date')->take(5)->get(),
        ]);
    }
}
