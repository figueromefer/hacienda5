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

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
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
                            <p class="text-gray-500">No hay movimientos registrados.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white shadow rounded p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Documentos</h3>
                        <a href="{{ route('documents.create', ['event_id' => $event->id]) }}" class="px-3 py-2 bg-black text-white rounded text-sm">+ Documento</a>
                    </div>

                    <div class="space-y-3">
                        @forelse($event->documents as $document)
                            <div class="border rounded-xl p-4 flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="font-semibold truncate">{{ $document->original_name }}</div>
                                    <div class="text-sm text-gray-500">
                                        {{ $document->category }} · {{ number_format(($document->file_size ?? 0) / 1024, 1) }} KB
                                    </div>
                                    @if($document->notes)
                                        <div class="text-sm text-gray-600 mt-1">{{ $document->notes }}</div>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white transition" title="Ver">👁</a>
                                    <form action="{{ route('documents.destroy', $document) }}" method="POST" onsubmit="return confirm('¿Eliminar este documento?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-red-50 text-red-700 hover:bg-red-600 hover:text-white transition" title="Eliminar">🗑</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="border border-dashed rounded-xl p-6 text-center text-gray-500">
                                No hay documentos cargados para este evento.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded p-6">
                <h3 class="text-lg font-semibold mb-6">Timeline del evento</h3>

                <div class="relative">
                    <div class="absolute left-4 top-0 bottom-0 w-px bg-gray-200"></div>

                    <div class="space-y-6">
                        @forelse($timeline as $item)
                            @php
                                $colorClasses = match($item['color']) {
                                    'green' => 'bg-green-100 text-green-700 ring-green-200',
                                    'red' => 'bg-red-100 text-red-700 ring-red-200',
                                    'blue' => 'bg-blue-100 text-blue-700 ring-blue-200',
                                    default => 'bg-gray-100 text-gray-700 ring-gray-200',
                                };
                            @endphp

                            <div class="relative flex gap-4 pl-12">
                                <div class="absolute left-0 top-1 flex h-8 w-8 items-center justify-center rounded-full ring-4 {{ $colorClasses }}">
                                    @if($item['type'] === 'Ingreso')
                                        $
                                    @elseif($item['type'] === 'Gasto')
                                        -
                                    @elseif($item['type'] === 'Documento')
                                        📎
                                    @else
                                        📝
                                    @endif
                                </div>

                                <div class="flex-1 rounded-xl border bg-gray-50 p-4">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-1">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900">{{ $item['title'] }}</div>
                                            <div class="text-sm text-gray-600 mt-1">{{ $item['description'] }}</div>
                                        </div>
                                        <div class="text-xs text-gray-500 whitespace-nowrap">
                                            {{ $item['date']?->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                    <div class="mt-2 inline-flex rounded-full bg-white px-2 py-1 text-xs text-gray-500 border">
                                        {{ $item['type'] }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="border border-dashed rounded-xl p-6 text-center text-gray-500">
                                Todavía no hay actividad registrada en este evento.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>