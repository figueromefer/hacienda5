<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Clientes</h2>
            <a href="{{ route('clients.create') }}" class="px-4 py-2 bg-black text-white rounded">Nuevo cliente</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                @if(session('success'))
                    <div class="mb-4 text-green-700">{{ session('success') }}</div>
                @endif

                <table class="w-full text-left border-collapse">
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
                                <td class="py-2">{{ $client->full_name }}</td>
                                <td class="py-2">{{ $client->type }}</td>
                                <td class="py-2">{{ $client->email }}</td>
                                <td class="py-2">{{ $client->phone }}</td>
                                <td class="py-2 flex gap-2">
                                    <a href="{{ route('clients.show', $client) }}" class="text-blue-600">Ver</a>
                                    <a href="{{ route('clients.edit', $client) }}" class="text-yellow-600">Editar</a>
                                    <form action="{{ route('clients.destroy', $client) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600">Eliminar</button>
                                    </form>
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