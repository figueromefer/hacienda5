<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use App\Services\FinancialBalanceCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::with('user')->latest()->paginate(15);

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:prospect,active,past'],
            'full_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],
            'source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'portal_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'password' => $data['portal_password'],
                'phone' => $data['phone'] ?? null,
                'is_active' => true,
            ]);

            $user->assignRole('cliente');

            Client::create([
                'user_id' => $user->id,
                'type' => $data['type'],
                'full_name' => $data['full_name'],
                'company_name' => $data['company_name'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'alternate_phone' => $data['alternate_phone'] ?? null,
                'source' => $data['source'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return redirect()->route('clients.index')->with('success', 'Cliente creado correctamente.');
    }

    public function show(Client $client, FinancialBalanceCalculator $balanceCalculator)
    {
        $client->load([
            'user',
            'events.quotations:id,event_id,status,total',
            'events.transactions:id,event_id,type,status,amount,transaction_date',
            'quotations',
            'payments',
            'documents',
            'transactions.event',
        ]);

        $eventBalances = $balanceCalculator->forEvents($client->events);

        return view('clients.show', compact('client', 'eventBalances'));
    }

    public function edit(Client $client)
    {
        $client->load('user');

        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $client->load('user');

        $data = $request->validate([
            'type' => ['required', 'in:prospect,active,past'],
            'full_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($client->user_id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],
            'source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'portal_password' => [
                Rule::requiredIf(! $client->user),
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        DB::transaction(function () use ($client, $data) {
            $user = $client->user ?? new User(['is_active' => true]);

            $user->fill([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ]);

            if (! empty($data['portal_password'])) {
                $user->password = $data['portal_password'];
            }

            $user->save();
            $user->syncRoles(['cliente']);

            $client->update([
                'user_id' => $user->id,
                'type' => $data['type'],
                'full_name' => $data['full_name'],
                'company_name' => $data['company_name'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'alternate_phone' => $data['alternate_phone'] ?? null,
                'source' => $data['source'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return redirect()->route('clients.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Cliente eliminado correctamente.');
    }
}
