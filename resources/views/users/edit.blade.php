<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar usuario</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="block mb-1">Nombre</label>
                        <input id="name" type="text" name="name" class="w-full border rounded" value="{{ old('name', $user->name) }}" required>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <label for="email" class="block mb-1">Email</label>
                        <input id="email" type="email" name="email" class="w-full border rounded" value="{{ old('email', $user->email) }}" required>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <label for="phone" class="block mb-1">Teléfono</label>
                        <input id="phone" type="text" name="phone" class="w-full border rounded" value="{{ old('phone', $user->phone) }}" inputmode="numeric" pattern="[0-9]*" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>

                    <div>
                        <label for="role" class="block mb-1">Rol</label>
                        <select id="role" name="role" class="w-full border rounded" required>
                            <option value="">Selecciona</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected($user->roles->contains('name', $role->name))>{{ \App\Support\DomainLabels::role($role->name) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="block mb-1">Nueva contraseña</label>
                        <input id="password" type="password" name="password" class="w-full border rounded" autocomplete="new-password">
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password_confirmation" class="block mb-1">Confirmar nueva contraseña</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="w-full border rounded" autocomplete="new-password">
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))>
                            <span>Activo</span>
                        </label>
                    </div>

                    <button class="px-4 py-2 bg-black text-white rounded">Actualizar usuario</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
