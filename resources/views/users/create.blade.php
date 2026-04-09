<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo usuario</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block mb-1">Nombre</label>
                        <input type="text" name="name" class="w-full border rounded" value="{{ old('name') }}">
                    </div>

                    <div>
                        <label class="block mb-1">Email</label>
                        <input type="email" name="email" class="w-full border rounded" value="{{ old('email') }}">
                    </div>

                    <div>
                        <label class="block mb-1">Teléfono</label>
                        <input type="text" name="phone" class="w-full border rounded" value="{{ old('phone') }}">
                    </div>

                    <div>
                        <label class="block mb-1">Rol</label>
                        <select name="role" class="w-full border rounded">
                            <option value="">Selecciona</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1">Contraseña</label>
                        <input type="password" name="password" class="w-full border rounded">
                    </div>

                    <div>
                        <label class="block mb-1">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation" class="w-full border rounded">
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span>Activo</span>
                        </label>
                    </div>

                    <button class="px-4 py-2 bg-black text-white rounded">Guardar usuario</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>