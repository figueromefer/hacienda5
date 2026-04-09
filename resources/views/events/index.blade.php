<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Eventos</h2>
            <a href="{{ route('events.create') }}" class="px-4 py-2 bg-black text-white rounded">Nuevo evento</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6 overflow-x-auto">
                <table class="w-full text-left border-collapse">
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
                                <td class="py-2">{{ $event->title }}</td>
                                <td class="py-2">{{ $event->client->full_name }}</td>
                                <td class="py-2">{{ $event->event_type }}</td>
                                <td class="py-2">{{ $event->event_date->format('d/m/Y') }}</td>
                                <td class="py-2">{{ $event->status }}</td>
                                <td class="py-2 flex gap-2">
                                    <a href="{{ route('events.show', $event) }}" class="text-blue-600">Ver</a>
                                    <a href="{{ route('events.edit', $event) }}" class="text-yellow-600">Editar</a>
                                    <form action="{{ route('events.destroy', $event) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600">Eliminar</button>
                                    </form>
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