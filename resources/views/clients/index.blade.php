<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Clientes</h2>
            <a href="{{ route('clients.create') }}" class="px-4 py-2 bg-black text-white rounded">Nuevo cliente</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-4 sm:p-6">
                @if(session('success'))
                    <div class="mb-4 text-green-700">{{ session('success') }}</div>
                @endif

                <table class="responsive-table w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Nombre</th>
                            <th class="py-2">Tipo</th>
                            <th class="py-2">Email</th>
                            <th class="py-2">Teléfono</th>
                            <th class="py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                            <tr class="border-b">
                                <td data-label="Nombre" class="py-2">{{ $client->full_name }}</td>
                                <td data-label="Tipo" class="py-2">{{ $client->type }}</td>
                                <td data-label="Email" class="py-2 break-all">{{ $client->email }}</td>
                                <td data-label="Teléfono" class="py-2">{{ $client->phone }}</td>
                                <td data-label="Acciones" class="py-2">
                                    <x-action-buttons
                                        :show="route('clients.show', $client)"
                                        :edit="route('clients.edit', $client)"
                                        :delete="route('clients.destroy', $client)"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4">No hay clientes registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $clients->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
