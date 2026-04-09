<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Documentos</h2>
            <a href="{{ route('documents.create') }}" class="px-4 py-2 bg-black text-white rounded">
                Subir documento
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6 overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Archivo</th>
                            <th class="py-2">Cliente</th>
                            <th class="py-2">Evento</th>
                            <th class="py-2">Categoría</th>
                            <th class="py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $document)
                            <tr class="border-b">
                                <td class="py-2">{{ $document->original_name }}</td>
                                <td class="py-2">{{ $document->client?->full_name ?? '-' }}</td>
                                <td class="py-2">{{ $document->event?->title ?? '-' }}</td>
                                <td class="py-2">{{ $document->category }}</td>
                                <td class="py-2 flex gap-2">
                                    <a href="{{ route('documents.show', $document) }}" class="text-blue-600">Ver</a>
                                    <form action="{{ route('documents.destroy', $document) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4">No hay documentos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $documents->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>