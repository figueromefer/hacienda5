<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detalle del pago</h2>
            <a href="{{ route('payments.edit', $payment) }}" class="px-4 py-2 bg-black text-white rounded">
                Editar
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6 space-y-4">
                <div><strong>Cliente:</strong> {{ $payment->client->full_name }}</div>
                <div><strong>Evento:</strong> {{ $payment->event?->title ?? 'Sin evento' }}</div>
                <div><strong>Cotización:</strong> {{ $payment->quotation?->folio ?? 'Sin cotización' }}</div>
                <div><strong>Fecha:</strong> {{ $payment->payment_date->format('d/m/Y') }}</div>
                <div><strong>Monto:</strong> ${{ number_format($payment->amount, 2) }}</div>
                <div><strong>Método:</strong> {{ $payment->method }}</div>
                <div><strong>Estatus:</strong> {{ $payment->status }}</div>
                <div><strong>Referencia:</strong> {{ $payment->reference ?? '-' }}</div>

                @if($payment->notes)
                    <div><strong>Notas:</strong> {{ $payment->notes }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>