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
            <div class="bg-white shadow rounded p-6">
                <table class="responsive-table w-full text-left border-collapse">
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
                                <td data-label="Archivo" class="py-2 break-all">{{ $document->original_name }}</td>
                                <td data-label="Cliente" class="py-2">{{ $document->client?->full_name ?? '-' }}</td>
                                <td data-label="Evento" class="py-2">{{ $document->event?->title ?? '-' }}</td>
                                <td data-label="Categoría" class="py-2">{{ $document->category }}</td>
                                <td data-label="Acciones" class="py-2">
                                    <x-action-buttons
                                        :show="route('documents.show', $document)"
                                        :delete="route('documents.destroy', $document)"
                                    />
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
