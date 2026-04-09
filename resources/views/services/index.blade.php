<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Servicios</h2>
            <a href="{{ route('services.create') }}" class="px-4 py-2 bg-black text-white rounded">Nuevo servicio</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6 overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Nombre</th>
                            <th class="py-2">Precio base</th>
                            <th class="py-2">Activo</th>
                            <th class="py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $service)
                            <tr class="border-b">
                                <td class="py-2">{{ $service->name }}</td>
                                <td class="py-2">${{ number_format($service->base_price, 2) }}</td>
                                <td class="py-2">{{ $service->is_active ? 'Sí' : 'No' }}</td>
                                <td class="py-2 flex gap-2">
                                    <a href="{{ route('services.show', $service) }}" class="text-blue-600">Ver</a>
                                    <a href="{{ route('services.edit', $service) }}" class="text-yellow-600">Editar</a>
                                    <form action="{{ route('services.destroy', $service) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4">No hay servicios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $services->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>