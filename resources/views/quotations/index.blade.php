<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cotizaciones</h2>
            <a href="{{ route('quotations.create') }}" class="px-4 py-2 bg-black text-white rounded">
                Nueva cotización
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                @if(session('success'))
                    <div class="mb-4 text-green-700">{{ session('success') }}</div>
                @endif

                <table class="responsive-table w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Folio</th>
                            <th class="py-2">Cliente</th>
                            <th class="py-2">Evento</th>
                            <th class="py-2">Estatus</th>
                            <th class="py-2">Total</th>
                            <th class="py-2">Válida hasta</th>
                            <th class="py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($quotations as $quotation)
                            <tr class="border-b">
                                <td data-label="Folio" class="py-2">{{ $quotation->folio }}</td>
                                <td data-label="Cliente" class="py-2">{{ $quotation->client->full_name }}</td>
                                <td data-label="Evento" class="py-2">{{ $quotation->event?->title ?? 'Sin evento' }}</td>
                                <td data-label="Estatus" class="py-2">{{ $quotation->status }}</td>
                                <td data-label="Total" class="py-2">${{ number_format($quotation->total, 2) }}</td>
                                <td data-label="Válida hasta" class="py-2">{{ $quotation->valid_until?->format('d/m/Y') ?? '-' }}</td>
                                <td data-label="Acciones" class="py-2">
                                    <x-action-buttons
                                        :show="route('quotations.show', $quotation)"
                                        :download="route('quotations.pdf', $quotation)"
                                        :edit="route('quotations.edit', $quotation)"
                                        :delete="route('quotations.destroy', $quotation)"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4">No hay cotizaciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $quotations->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
