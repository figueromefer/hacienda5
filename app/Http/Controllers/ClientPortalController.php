<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientPortalController extends Controller
{
    public function index(Request $request)
    {
        $client = Client::with(['events', 'payments', 'documents'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return view('client-portal.index', compact('client'));
    }
}
