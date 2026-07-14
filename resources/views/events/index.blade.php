<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Eventos</h2>
            <a href="{{ route('events.create') }}" class="px-4 py-2 bg-black text-white rounded">Nuevo evento</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <table class="responsive-table w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Título</th>
                            <th class="py-2">Cliente</th>
                            <th class="py-2">Tipo</th>
                            <th class="py-2">Fecha</th>
                            <th class="py-2">Estatus</th>
                            <th class="py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                            <tr class="border-b">
                                <td data-label="Título" class="py-2">{{ $event->title }}</td>
                                <td data-label="Cliente" class="py-2">{{ $event->client->full_name }}</td>
                                <td data-label="Tipo" class="py-2">{{ $event->event_type }}</td>
                                <td data-label="Fecha" class="py-2">{{ $event->event_date->format('d/m/Y') }}</td>
                                <td data-label="Estatus" class="py-2">{{ $event->status }}</td>
                                <td data-label="Acciones" class="py-2">
                                    <x-action-buttons
                                        :show="route('events.show', $event)"
                                        :edit="route('events.edit', $event)"
                                        :delete="route('events.destroy', $event)"
                                        confirm="Esta acción eliminará el evento y su información relacionada. Para confirmar, escribe ELIMINAR."
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4">No hay eventos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $events->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
