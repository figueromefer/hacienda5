<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo verificado - Hacienda Cinco</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
    <div class="min-h-screen">
        <header class="bg-[#243834] text-white">
            <div class="max-w-5xl mx-auto px-6 py-6 flex items-center gap-5">
                <img src="{{ asset('images/hacienda-cinco-logo.png') }}" alt="Hacienda Cinco" class="w-24 h-auto">
                <div>
                    <div class="tracking-[0.35em] text-lg">HACIENDA CINCO</div>
                    <div class="tracking-[0.45em] text-xs opacity-80">LA VICTORIA</div>
                </div>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-6 py-10">
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
                <div class="p-8 border-b {{ $transaction->status === \App\Models\Transaction::STATUS_CANCELLED ? 'bg-red-50' : 'bg-green-50' }}">
                    <div class="inline-flex items-center gap-3 rounded-full px-4 py-2 text-sm font-semibold {{ $transaction->status === \App\Models\Transaction::STATUS_CANCELLED ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                        <span class="h-3 w-3 rounded-full {{ $transaction->status === \App\Models\Transaction::STATUS_CANCELLED ? 'bg-red-600' : 'bg-green-600' }}"></span>
                        {{ $transaction->status === \App\Models\Transaction::STATUS_CANCELLED ? 'RECIBO CANCELADO' : 'RECIBO VERIFICADO EN SISTEMA' }}
                    </div>
                    <h1 class="mt-6 text-3xl font-bold">{{ $receiptTitle }} #{{ $transaction->id }}</h1>
                    <p class="mt-2 text-gray-600">{{ $transaction->status === \App\Models\Transaction::STATUS_CANCELLED ? 'Este movimiento se conserva solo para auditoría y no afecta cifras financieras.' : 'Este recibo existe y está vigente en el sistema oficial de Hacienda Cinco.' }}</p>
                </div>

                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="rounded-2xl border p-5">
                        <div class="text-sm text-gray-500">Cliente / relacionado</div>
                        <div class="mt-1 text-lg font-semibold">{{ $transaction->client?->full_name ?? 'Sin cliente' }}</div>
                    </div>
                    <div class="rounded-2xl border p-5">
                        <div class="text-sm text-gray-500">Fecha del movimiento</div>
                        <div class="mt-1 text-lg font-semibold">{{ $transaction->transaction_date?->format('d/m/Y') }}</div>
                    </div>
                    <div class="rounded-2xl border p-5">
                        <div class="text-sm text-gray-500">Monto registrado</div>
                        <div class="mt-1 text-2xl font-bold {{ $transaction->type === 'income' ? 'text-green-700' : 'text-red-700' }}">${{ number_format($transaction->amount, 2) }}</div>
                        <div class="mt-2 text-xs text-gray-500">{{ $amountInWords }}</div>
                    </div>
                    <div class="rounded-2xl border p-5">
                        <div class="text-sm text-gray-500">Tipo</div>
                        <div class="mt-1 text-lg font-semibold">{{ $transaction->type_label }}</div>
                    </div>
                    <div class="rounded-2xl border p-5 md:col-span-2">
                        <div class="text-sm text-gray-500">Referencia</div>
                        <div class="mt-1 text-lg font-semibold">{{ $transaction->reference ?: '-' }}</div>
                    </div>
                    <div class="rounded-2xl border p-5 md:col-span-2">
                        <div class="text-sm text-gray-500">Concepto</div>
                        <div class="mt-1 text-lg font-semibold">{{ $transaction->category ?: 'Movimiento registrado' }}</div>
                        @if($transaction->notes)
                            <p class="mt-2 text-gray-600">{{ $transaction->notes }}</p>
                        @endif
                    </div>
                    @if($transaction->event)
                        <div class="rounded-2xl border p-5 md:col-span-2">
                            <div class="text-sm text-gray-500">Evento vinculado</div>
                            <div class="mt-1 text-lg font-semibold">{{ $transaction->event->title }}</div>
                            <div class="mt-1 text-sm text-gray-600">
                                {{ $transaction->event->event_type }} · {{ $transaction->event->event_date?->format('d/m/Y') }}
                            </div>
                        </div>
                    @endif
                </div>

                <div class="px-8 pb-8">
                    <div class="rounded-2xl bg-gray-50 border p-5 text-sm text-gray-600">
                        Si los datos impresos en un recibo físico o PDF no coinciden con esta pantalla, usa esta pantalla como fuente oficial.
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
