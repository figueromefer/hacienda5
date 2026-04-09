<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detalle del servicio</h2>
            <a href="{{ route('services.edit', $service) }}" class="px-4 py-2 bg-black text-white rounded">
                Editar
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6 space-y-4">
                <div><strong>Nombre:</strong> {{ $service->name }}</div>
                <div><strong>Precio base:</strong> ${{ number_format($service->base_price, 2) }}</div>
                <div><strong>Activo:</strong> {{ $service->is_active ? 'Sí' : 'No' }}</div>

                @if($service->description)
                    <div>
                        <strong>Descripción:</strong>
                        <p class="mt-1">{{ $service->description }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>