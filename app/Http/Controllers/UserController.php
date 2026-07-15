<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'cliente'))
            ->latest()
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create', [
            'roles' => $this->administrativeRoles(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\\d+$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::exists('roles', 'name')->whereNot('name', 'cliente')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $user->assignRole($data['role']);

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user)
    {
        $this->ensureAdministrativeUser($user);

        return view('users.edit', [
            'user' => $user,
            'roles' => $this->administrativeRoles(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureAdministrativeUser($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\\d+$/'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::exists('roles', 'name')->whereNot('name', 'cliente')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'password' => ! empty($data['password']) ? Hash::make($data['password']) : $user->password,
        ]);

        $user->syncRoles([$data['role']]);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        $this->ensureAdministrativeUser($user);

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
    }

    private function administrativeRoles()
    {
        return Role::where('name', '!=', 'cliente')->orderBy('name')->get();
    }

    private function ensureAdministrativeUser(User $user): void
    {
        abort_if($user->hasRole('cliente'), 404);
    }
}
