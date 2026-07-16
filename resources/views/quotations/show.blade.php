<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Cotización {{ $quotation->folio }}
            </h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('quotations.pdf', $quotation) }}" class="rounded bg-emerald-700 px-4 py-2 text-white hover:bg-emerald-800">
                    Descargar PDF
                </a>
                <a href="{{ route('quotations.edit', $quotation) }}" class="rounded bg-black px-4 py-2 text-white">
                    Editar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow rounded p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><strong>Cliente:</strong> {{ $quotation->client->full_name }}</div>
                    <div><strong>Evento:</strong> {{ $quotation->event?->title ?? 'Sin evento' }}</div>
                    <div><strong>Estatus:</strong> {{ $quotation->status_label }}</div>
                    <div><strong>Válida hasta:</strong> {{ $quotation->valid_until?->format('d/m/Y') ?? '-' }}</div>
                </div>

                @if($quotation->notes)
                    <div class="mt-4">
                        <strong>Notas:</strong>
                        <p class="mt-1 text-gray-700">{{ $quotation->notes }}</p>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow rounded p-6">
                <table class="responsive-table w-full border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2 text-left">Descripción</th>
                            <th class="py-2 text-left">Cantidad</th>
                            <th class="py-2 text-left">Precio unitario</th>
                            <th class="py-2 text-left">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quotation->items as $item)
                            <tr class="border-b">
                                <td data-label="Descripción" class="py-2">{{ $item->description }}</td>
                                <td data-label="Cantidad" class="py-2">{{ $item->quantity }}</td>
                                <td data-label="Precio unitario" class="py-2">$ {{ number_format($item->unit_price, 2) }}</td>
                                <td data-label="Total" class="py-2">$ {{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-6 max-w-sm ml-auto space-y-2">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span>$ {{ number_format($quotation->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Descuento</span>
                        <span>$ {{ number_format($quotation->discount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t pt-2">
                        <span>Total</span>
                        <span>$ {{ number_format($quotation->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
