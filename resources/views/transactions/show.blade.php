<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Recibo #{{ $transaction->id }}</h2>
                <p class="text-sm text-gray-500 mt-1">Vista administrativa del recibo registrado en sistema.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('transactions.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Volver</a>
                <a href="{{ route('transactions.edit', $transaction) }}" class="px-4 py-2 bg-amber-100 text-amber-800 rounded-lg hover:bg-amber-200">Editar</a>
                @if($transaction->receipt_token)
                    <a href="{{ route('receipts.public.show', $transaction->receipt_token) }}" target="_blank" class="px-4 py-2 bg-green-100 text-green-800 rounded-lg hover:bg-green-200">Validar</a>
                @endif
                <a href="{{ route('transactions.pdf', $transaction) }}" class="px-4 py-2 bg-[#243834] text-white rounded-lg hover:opacity-90">Descargar PDF</a>
            </div>
        </div>
    </x-slot>

    @php
        $isIncome = $transaction->type === \App\Models\Transaction::TYPE_INCOME;
        $clientName = $transaction->client?->full_name ?? 'SIN CLIENTE';
        $event = $transaction->event;
        $concept = $transaction->category ?: ($transaction->notes ?: 'Movimiento registrado');
        $signer = $isIncome ? 'ALEJANDRO AGUILAR GANDARA' : ($transaction->reference ?: 'RECIBI DE CONFORMIDAD');
    @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
                <div class="bg-[#243834] px-8 py-7 flex items-center gap-6">
                    <img src="{{ asset('images/hacienda-cinco-logo.png') }}" class="w-28 h-auto" alt="Hacienda Cinco">
                    <div class="text-white">
                        <div class="tracking-[0.35em] text-lg">HACIENDA CINCO</div>
                        <div class="tracking-[0.45em] text-sm opacity-90">LA VICTORIA</div>
                    </div>
                </div>

                <div class="p-8 md:p-12">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-10">
                        <div class="rounded-2xl border bg-gray-50 p-4">
                            <div class="text-xs uppercase text-gray-500">Fecha</div>
                            <div class="font-semibold mt-1">{{ $transaction->transaction_date?->format('d/m/Y') }}</div>
                        </div>
                        <div class="rounded-2xl border bg-gray-50 p-4">
                            <div class="text-xs uppercase text-gray-500">Tipo</div>
                            <div class="font-semibold mt-1">{{ $transaction->type_label }}</div>
                        </div>
                        <div class="rounded-2xl border bg-gray-50 p-4">
                            <div class="text-xs uppercase text-gray-500">Estatus</div>
                            <div class="font-semibold mt-1">{{ $transaction->status }}</div>
                        </div>
                    </div>

                    <h1 class="text-2xl md:text-3xl font-bold text-center mb-10">{{ $receiptTitle }}</h1>

                    <div class="rounded-2xl border p-6 md:p-8">
                        <p class="text-lg leading-9 text-justify">
                            @if($isIncome)
                                Recibí la cantidad de <strong>${{ number_format($transaction->amount, 2) }}</strong>
                                (<strong>{{ $amountInWords }}</strong>),
                                de <strong>{{ mb_strtoupper($clientName) }}</strong>
                                concepto de “<strong>{{ mb_strtoupper($concept) }}</strong>”
                                @if($event)
                                    para <strong>{{ mb_strtoupper($event->event_type ?: 'EVENTO') }}</strong>,
                                    a llevarse a cabo <strong>{{ $event->event_date?->format('d/m/Y') }}</strong>.
                                    @if($event->guest_count)
                                        <strong>{{ $event->guest_count }} PERSONAS</strong>.
                                    @endif
                                @endif
                            @else
                                Recibí la cantidad de <strong>${{ number_format($transaction->amount, 2) }}</strong>
                                (<strong>{{ $amountInWords }}</strong>),
                                de <strong>{{ mb_strtoupper($clientName) }}</strong>
                                concepto “<strong>{{ mb_strtoupper($concept) }}</strong>”.
                            @endif
                        </p>

                        @if($transaction->notes && $transaction->category)
                            <div class="mt-8 rounded-xl bg-gray-50 border p-4 text-gray-700">
                                <strong>Notas:</strong> {{ $transaction->notes }}
                            </div>
                        @endif

                        <div class="mt-24 text-center font-bold">
                            <div class="border-t border-gray-800 w-80 max-w-full mx-auto mb-3"></div>
                            {{ mb_strtoupper($signer) }}
                        </div>

                        @if($isIncome)
                            <p class="mt-16 text-sm leading-6 font-bold text-justify text-gray-700">
                                POLÍTICA DE CANCELACIÓN. EL CLIENTE CUENTA CON 5 DÍAS NATURALES A PARTIR DEL SIGUIENTE APARTADO PARA CANCELAR EL EVENTO SIN PENALIZACIÓN, POSTERIORMENTE SE SUJETARÁ A NUESTRAS POLÍTICAS DE CANCELACIÓN LAS CUALES LLEVAN PENALIZACIÓN.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            @if($transaction->receipt_token)
                <div class="bg-white rounded-3xl shadow p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">Verificación pública</h3>
                        <p class="text-sm text-gray-600 mt-1">Esta URL es la fuente oficial para validar que el recibo no fue alterado.</p>
                        <div class="mt-2 text-xs text-gray-500 break-all">{{ route('receipts.public.show', $transaction->receipt_token) }}</div>
                    </div>
                    <a href="{{ route('receipts.public.show', $transaction->receipt_token) }}" target="_blank" class="px-4 py-2 bg-green-100 text-green-800 rounded-lg hover:bg-green-200 text-center">Abrir validación</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
