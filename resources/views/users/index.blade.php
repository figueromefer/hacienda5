<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Usuarios</h2>
            <a href="{{ route('users.create') }}" class="px-4 py-2 bg-black text-white rounded">
                Nuevo usuario
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <table class="responsive-table w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Nombre</th>
                            <th class="py-2">Email</th>
                            <th class="py-2">Teléfono</th>
                            <th class="py-2">Rol</th>
                            <th class="py-2">Activo</th>
                            <th class="py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr class="border-b">
                                <td data-label="Nombre" class="py-2">{{ $user->name }}</td>
                                <td data-label="Email" class="py-2 break-all">{{ $user->email }}</td>
                                <td data-label="Teléfono" class="py-2">{{ $user->phone ?? '-' }}</td>
                                <td data-label="Rol" class="py-2">{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</td>
                                <td data-label="Activo" class="py-2">{{ $user->is_active ? 'Sí' : 'No' }}</td>
                                <td data-label="Acciones" class="py-2">
                                    <x-action-buttons
                                        :edit="route('users.edit', $user)"
                                        :delete="route('users.destroy', $user)"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4">No hay usuarios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
