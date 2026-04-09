<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],
            'source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],

            'create_portal_access' => ['nullable', 'boolean'],
            'portal_email' => ['nullable', 'email', 'max:255', 'required_if:create_portal_access,1', 'unique:users,email'],
            'portal_password' => ['nullable', 'string', 'min:8', 'required_if:create_portal_access,1'],
        ]);

        DB::transaction(function () use ($data) {
            $clientData = [
                'type' => $data['type'],
                'full_name' => $data['full_name'],
                'company_name' => $data['company_name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'alternate_phone' => $data['alternate_phone'] ?? null,
                'source' => $data['source'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            if (!empty($data['create_portal_access'])) {
                $user = User::create([
                    'name' => $data['full_name'],
                    'email' => $data['portal_email'],
                    'password' => $data['portal_password'],
                    'phone' => $data['phone'] ?? null,
                    'is_active' => true,
                ]);

                $user->assignRole('cliente');

                $clientData['user_id'] = $user->id;

                if (empty($clientData['email'])) {
                    $clientData['email'] = $user->email;
                }
            }

            Client::create($clientData);
        });

        return redirect()->route('clients.index')->with('success', 'Cliente creado correctamente.');
    }

    public function show(Client $client)
    {
        $client->load([
            'user',
            'events',
            'quotations',
            'payments',
            'documents',
        ]);

        return view('clients.show', compact('client'));
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
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],
            'source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],

            'create_portal_access' => ['nullable', 'boolean'],
            'portal_email' => [
                'nullable',
                'email',
                'max:255',
                'required_if:create_portal_access,1',
                Rule::unique('users', 'email')->ignore($client->user_id),
            ],
            'portal_password' => ['nullable', 'string', 'min:8'],
        ]);

        DB::transaction(function () use ($client, $data) {
            $clientData = [
                'type' => $data['type'],
                'full_name' => $data['full_name'],
                'company_name' => $data['company_name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'alternate_phone' => $data['alternate_phone'] ?? null,
                'source' => $data['source'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            if (!empty($data['create_portal_access'])) {
                if ($client->user) {
                    $user = $client->user;

                    $user->name = $data['full_name'];
                    $user->email = $data['portal_email'];
                    $user->phone = $data['phone'] ?? null;
                    $user->is_active = true;

                    if (!empty($data['portal_password'])) {
                        $user->password = $data['portal_password'];
                    }

                    $user->save();

                    if (!$user->hasRole('cliente')) {
                        $user->assignRole('cliente');
                    }
                } else {
                    $user = User::create([
                        'name' => $data['full_name'],
                        'email' => $data['portal_email'],
                        'password' => $data['portal_password'] ?: Str::random(12),
                        'phone' => $data['phone'] ?? null,
                        'is_active' => true,
                    ]);

                    $user->assignRole('cliente');
                }

                $clientData['user_id'] = $user->id;

                if (empty($clientData['email'])) {
                    $clientData['email'] = $user->email;
                }
            }

            $client->update($clientData);
        });

        return redirect()->route('clients.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Cliente eliminado correctamente.');
    }
}