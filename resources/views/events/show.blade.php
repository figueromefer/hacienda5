<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $event->title }}
            </h2>
            <a href="{{ route('events.edit', $event) }}" class="px-4 py-2 bg-black text-white rounded">
                Editar evento
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div><strong>Cliente:</strong> {{ $event->client->full_name }}</div>
                    <div><strong>Tipo:</strong> {{ $event->event_type }}</div>
                    <div><strong>Estatus:</strong> {{ $event->status }}</div>
                    <div><strong>Fecha:</strong> {{ $event->event_date->format('d/m/Y') }}</div>
                    <div><strong>Invitados:</strong> {{ $event->guest_count ?? '-' }}</div>
                    <div><strong>Total:</strong> ${{ number_format($event->total_amount, 2) }}</div>
                </div>
            </div>

            <!-- BALANCE -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white shadow rounded p-5">
                    <div class="text-sm text-gray-500">Ingresos</div>
                    <div class="text-xl font-bold text-green-700">${{ number_format($income, 2) }}</div>
                </div>
                <div class="bg-white shadow rounded p-5">
                    <div class="text-sm text-gray-500">Gastos</div>
                    <div class="text-xl font-bold text-red-700">${{ number_format($expenses, 2) }}</div>
                </div>
                <div class="bg-white shadow rounded p-5">
                    <div class="text-sm text-gray-500">Pendiente por cobrar</div>
                    <div class="text-xl font-bold text-yellow-600">${{ number_format($pendingIncome, 2) }}</div>
                </div>
                <div class="bg-white shadow rounded p-5">
                    <div class="text-sm text-gray-500">Balance</div>
                    <div class="text-xl font-bold {{ $balance >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        ${{ number_format($balance, 2) }}
                    </div>
                </div>
            </div>

            <!-- MOVIMIENTOS -->
            <div class="bg-white shadow rounded p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Movimientos</h3>
                    <div class="flex gap-2">
                        <a href="{{ route('transactions.create', ['type' => 'income', 'event_id' => $event->id]) }}" class="px-3 py-2 bg-green-600 text-white rounded text-sm">+ Ingreso</a>
                        <a href="{{ route('transactions.create', ['type' => 'expense', 'event_id' => $event->id]) }}" class="px-3 py-2 bg-red-600 text-white rounded text-sm">+ Gasto</a>
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse($event->transactions as $transaction)
                        <div class="border rounded p-3 flex justify-between items-center">
                            <div>
                                <div class="font-semibold">
                                    {{ $transaction->type === 'income' ? 'Ingreso' : 'Gasto' }} - ${{ number_format($transaction->amount, 2) }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    {{ $transaction->transaction_date->format('d/m/Y') }} · {{ $transaction->category ?? 'Sin categoría' }}
                                </div>
                            </div>
                            <div class="text-sm {{ $transaction->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transaction->status }}
                            </div>
                        </div>
                    @empty
                        <p>No hay movimientos registrados.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>