<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Movimientos</h2>
            <a href="{{ route('transactions.create') }}" class="px-4 py-2 bg-black text-white rounded">
                Nuevo movimiento
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded p-4">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white shadow rounded p-5">
                    <div class="text-sm text-gray-500">Ingresos pagados</div>
                    <div class="text-2xl font-bold text-green-700">${{ number_format($income, 2) }}</div>
                </div>
                <div class="bg-white shadow rounded p-5">
                    <div class="text-sm text-gray-500">Gastos pagados</div>
                    <div class="text-2xl font-bold text-red-700">${{ number_format($expenses, 2) }}</div>
                </div>
                <div class="bg-white shadow rounded p-5">
                    <div class="text-sm text-gray-500">Balance</div>
                    <div class="text-2xl font-bold {{ $balance >= 0 ? 'text-green-700' : 'text-red-700' }}">${{ number_format($balance, 2) }}</div>
                </div>
            </div>

            <div class="bg-white shadow rounded p-6">
                <form method="GET" action="{{ route('transactions.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                    <div>
                        <label class="block mb-1 text-sm">Tipo</label>
                        <select name="type" class="w-full border rounded">
                            <option value="">Todos</option>
                            <option value="income" @selected(request('type') === 'income')>Ingresos</option>
                            <option value="expense" @selected(request('type') === 'expense')>Gastos</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm">Alcance</label>
                        <select name="scope" class="w-full border rounded">
                            <option value="">Todos</option>
                            <option value="event" @selected(request('scope') === 'event')>Evento</option>
                            <option value="operation" @selected(request('scope') === 'operation')>Operación</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm">Evento</label>
                        <select name="event_id" class="w-full border rounded">
                            <option value="">Todos</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" @selected((string) request('event_id') === (string) $event->id)>{{ $event->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm">Desde</label>
                        <input type="date" name="from" class="w-full border rounded" value="{{ request('from') }}">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm">Hasta</label>
                        <input type="date" name="to" class="w-full border rounded" value="{{ request('to') }}">
                    </div>
                    <div class="flex gap-2">
                        <button class="px-4 py-2 bg-black text-white rounded">Filtrar</button>
                        <a href="{{ route('transactions.index') }}" class="px-4 py-2 bg-gray-200 rounded">Limpiar</a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow rounded p-6 overflow-x-auto">
                <table class="w-full min-w-[1150px] text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Fecha</th>
                            <th class="py-2">Tipo</th>
                            <th class="py-2">Alcance</th>
                            <th class="py-2">Cliente</th>
                            <th class="py-2">Evento</th>
                            <th class="py-2">Categoría</th>
                            <th class="py-2 text-right">Monto</th>
                            <th class="py-2">Estatus</th>
                            <th class="py-2 w-[360px]">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr class="border-b">
                                <td class="py-2 whitespace-nowrap">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                <td class="py-2 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded text-xs {{ $transaction->type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $transaction->type_label }}
                                    </span>
                                </td>
                                <td class="py-2 whitespace-nowrap">{{ $transaction->scope_label }}</td>
                                <td class="py-2">{{ $transaction->client->full_name }}</td>
                                <td class="py-2">{{ $transaction->event?->title ?? '-' }}</td>
                                <td class="py-2">{{ $transaction->category ?? '-' }}</td>
                                <td class="py-2 text-right whitespace-nowrap {{ $transaction->type === 'income' ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $transaction->type === 'expense' ? '-' : '' }}${{ number_format($transaction->amount, 2) }}
                                </td>
                                <td class="py-2 whitespace-nowrap">{{ $transaction->status }}</td>
                                <td class="py-2">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('transactions.show', $transaction) }}" class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                            Ver recibo
                                        </a>
                                        <a href="{{ route('transactions.pdf', $transaction) }}" class="inline-flex items-center rounded-full bg-[#243834] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#1b2b28]">
                                            PDF
                                        </a>
                                        @if($transaction->receipt_token)
                                            <a href="{{ route('receipts.public.show', $transaction->receipt_token) }}" target="_blank" class="inline-flex items-center rounded-full bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700 hover:bg-green-100">
                                                Validar
                                            </a>
                                        @endif
                                        <a href="{{ route('transactions.edit', $transaction) }}" class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100">
                                            Editar
                                        </a>
                                        <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" onsubmit="return confirm('¿Eliminar este movimiento?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-4">No hay movimientos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
