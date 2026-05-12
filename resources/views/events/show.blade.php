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
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-700">
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-.988-2.386l-4.751-4.751A3.375 3.375 0 0011.375 3.5H8.25A2.25 2.25 0 006 5.75v12.5a2.25 2.25 0 002.25 2.25h9a2.25 2.25 0 002.25-2.25v-4z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75V9a2.25 2.25 0 002.25 2.25h5.25" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold truncate">{{ $document->original_name }}</div>
                                        <div class="text-sm text-gray-500">
                                            {{ $document->category }} · {{ number_format(($document->file_size ?? 0) / 1024, 1) }} KB
                                        </div>
                                        @if($document->notes)
                                            <div class="text-sm text-gray-600 mt-1">{{ $document->notes }}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white transition" title="Ver">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('documents.destroy', $document) }}" method="POST" onsubmit="return confirm('¿Eliminar este documento?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-red-50 text-red-700 hover:bg-red-600 hover:text-white transition" title="Eliminar">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M18.16 19.673A2.25 2.25 0 0115.916 21H8.084a2.25 2.25 0 01-2.244-1.327L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .563c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
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

        </div>
    </div>
</x-app-layout>