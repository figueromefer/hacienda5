<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Servicios</h2>
            <a href="{{ route('services.create') }}" class="px-4 py-2 bg-black text-white rounded">Nuevo servicio</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <table class="responsive-table w-full text-left border-collapse">
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
                                <td data-label="Nombre" class="py-2">{{ $service->name }}</td>
                                <td data-label="Precio base" class="py-2">${{ number_format($service->base_price, 2) }}</td>
                                <td data-label="Activo" class="py-2">{{ $service->is_active ? 'Sí' : 'No' }}</td>
                                <td data-label="Acciones" class="py-2">
                                    <x-action-buttons
                                        :show="route('services.show', $service)"
                                        :edit="route('services.edit', $service)"
                                        :delete="route('services.destroy', $service)"
                                    />
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
