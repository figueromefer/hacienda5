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
                        <label class="block mb-1">Nombre</label>
                        <input type="text" name="name" class="w-full border rounded" value="{{ old('name', $user->name) }}">
                    </div>

                    <div>
                        <label class="block mb-1">Email</label>
                        <input type="email" name="email" class="w-full border rounded" value="{{ old('email', $user->email) }}">
                    </div>

                    <div>
                        <label class="block mb-1">Teléfono</label>
                        <input type="text" name="phone" class="w-full border rounded" value="{{ old('phone', $user->phone) }}">
                    </div>

                    <div>
                        <label class="block mb-1">Rol</label>
                        <select name="role" class="w-full border rounded">
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected($user->roles->contains('name', $role->name))>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1">Nueva contraseña</label>
                        <input type="password" name="password" class="w-full border rounded">
                    </div>

                    <div>
                        <label class="block mb-1">Confirmar nueva contraseña</label>
                        <input type="password" name="password_confirmation" class="w-full border rounded">
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" @checked($user->is_active)>
                            <span>Activo</span>
                        </label>
                    </div>

                    <button class="px-4 py-2 bg-black text-white rounded">Actualizar usuario</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>