<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detalle del documento</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6 space-y-4">
                <div><strong>Archivo:</strong> {{ $document->original_name }}</div>
                <div><strong>Categoría:</strong> {{ $document->category }}</div>
                <div><strong>Cliente:</strong> {{ $document->client?->full_name ?? '-' }}</div>
                <div><strong>Evento:</strong> {{ $document->event?->title ?? '-' }}</div>
                <div><strong>Subido por:</strong> {{ $document->uploader?->name ?? '-' }}</div>
                <div><strong>Tamaño:</strong> {{ $document->file_size ? number_format($document->file_size / 1024, 2) . ' KB' : '-' }}</div>

                @if($document->notes)
                    <div><strong>Notas:</strong> {{ $document->notes }}</div>
                @endif

                <div>
                    <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="text-blue-600">
                        Abrir documento
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>