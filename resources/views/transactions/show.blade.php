<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Recibo de {{ $transaction->type === 'income' ? 'ingreso' : 'gasto' }} #{{ $transaction->id }}
            </h2>
            <div class="flex gap-2">
                <button onclick="window.print()" class="px-4 py-2 bg-black text-white rounded">Imprimir / PDF</button>
                <a href="{{ route('transactions.index') }}" class="px-4 py-2 bg-gray-200 rounded">Volver</a>
            </div>
        </div>
    </x-slot>

    @php
        $isIncome = $transaction->type === 'income';
        $dateText = strtoupper($transaction->transaction_date->translatedFormat('d-F-Y'));
        $amountText = '$' . number_format($transaction->amount, 2);
        $clientName = $transaction->client?->full_name ?? 'SIN CLIENTE';
        $eventText = $transaction->event
            ? ' para ' . strtoupper($transaction->event->event_type ?? 'EVENTO') . ', a llevarse a cabo ' . $transaction->event->event_date->format('d/m/Y') . '. ' . strtoupper($transaction->event->title)
            : '';
        $concept = $transaction->category ?: ($isIncome ? 'ANTICIPO' : 'PAGO');
        $receiver = $isIncome ? 'ALEJANDRO AGUILAR GÁNDARA' : strtoupper($transaction->reference ?: 'ALEJANDRO AGUILAR GÁNDARA');
    @endphp

    <div class="py-8 print:py-0">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-10 print:shadow-none print:rounded-none receipt-page">
                <div class="text-sm mb-16">{{ $dateText }}</div>

                <div class="text-center font-bold text-xl mb-10 uppercase">
                    {{ $isIncome ? 'RECIBO DE ANTICIPO' : 'RECIBO PAGO TRABAJOS' }}
                </div>

                <p class="text-lg leading-9 text-justify">
                    Recibí la cantidad de <strong>{{ $amountText }}</strong>,
                    {{ $isIncome ? $clientName : 'DE ' . $clientName }}
                    concepto {{ $isIncome ? 'de' : '' }} “<strong>{{ strtoupper($concept) }}</strong>”{{ $eventText }}.
                </p>

                @if($transaction->notes)
                    <p class="text-lg leading-9 mt-4 text-justify">
                        {{ $transaction->notes }}
                    </p>
                @endif

                <div class="mt-24 text-center">
                    <div class="border-t border-black w-80 mx-auto pt-3 uppercase">
                        {{ $receiver }}
                    </div>
                </div>

                @if($isIncome)
                    <div class="mt-20 text-sm leading-6 uppercase">
                        <strong>POLÍTICA DE CANCELACIÓN.</strong>
                        EL CLIENTE CUENTA CON 5 DÍAS NATURALES A PARTIR DEL SIGUIENTE APARTADO PARA CANCELAR EL EVENTO SIN PENALIZACIÓN, POSTERIORMENTE SE SUJETARÁ A NUESTRAS POLÍTICAS DE CANCELACIÓN LAS CUALES LLEVAN PENALIZACIÓN.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        @media print {
            body { background: white !important; }
            nav, header, .brand-page-header, button, a { display: none !important; }
            .receipt-page { padding: 0 !important; }
        }
    </style>
</x-app-layout>
