<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Cotización {{ $quotation->folio }}
            </h2>
            <a href="{{ route('quotations.edit', $quotation) }}" class="px-4 py-2 bg-black text-white rounded">
                Editar
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow rounded p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><strong>Cliente:</strong> {{ $quotation->client->full_name }}</div>
                    <div><strong>Evento:</strong> {{ $quotation->event?->title ?? 'Sin evento' }}</div>
                    <div><strong>Estatus:</strong> {{ $quotation->status }}</div>
                    <div><strong>Válida hasta:</strong> {{ $quotation->valid_until?->format('d/m/Y') ?? '-' }}</div>
                </div>

                @if($quotation->notes)
                    <div class="mt-4">
                        <strong>Notas:</strong>
                        <p class="mt-1 text-gray-700">{{ $quotation->notes }}</p>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow rounded p-6 overflow-x-auto">
                <table class="w-full border-collapse">
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
                                <td class="py-2">{{ $item->description }}</td>
                                <td class="py-2">{{ $item->quantity }}</td>
                                <td class="py-2">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="py-2">${{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-6 max-w-sm ml-auto space-y-2">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span>${{ number_format($quotation->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Descuento</span>
                        <span>${{ number_format($quotation->discount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t pt-2">
                        <span>Total</span>
                        <span>${{ number_format($quotation->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>