<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Movimientos</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('incomes.index') }}" class="rounded bg-green-700 px-4 py-2 text-white">Ingresos</a>
                <a href="{{ route('expenses.index') }}" class="rounded bg-red-700 px-4 py-2 text-white">Gastos</a>
                <a href="{{ route('transactions.create') }}" class="rounded bg-black px-4 py-2 text-white">Nuevo movimiento</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded p-4">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
                <div class="rounded border border-amber-200 bg-amber-50 p-4 text-amber-800">{{ session('warning') }}</div>
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
                <form method="GET" action="{{ route('transactions.index') }}" class="grid grid-cols-1 gap-4 items-end md:grid-cols-2 xl:grid-cols-7">
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
                    <div>
                        <label class="block mb-1 text-sm">Estado</label>
                        <select name="status" class="w-full border rounded">
                            <option value="">Todos</option>
                            <option value="paid" @selected(request('status') === 'paid')>Pagado</option>
                            <option value="pending" @selected(request('status') === 'pending')>Pendiente histórico</option>
                            <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelado</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button class="px-4 py-2 bg-black text-white rounded">Filtrar</button>
                        <a href="{{ route('transactions.index') }}" class="px-4 py-2 bg-gray-200 rounded">Limpiar</a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow rounded p-6 overflow-x-auto">
                <table class="responsive-table w-full min-w-[1250px] text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Fecha</th>
                            <th class="py-2">Referencia</th>
                            <th class="py-2">Tipo</th>
                            <th class="py-2">Alcance</th>
                            <th class="py-2">Cliente</th>
                            <th class="py-2">Evento</th>
                            <th class="py-2">Categoría</th>
                            <th class="py-2">Proveedor / concepto</th>
                            <th class="py-2 text-right">Monto</th>
                            <th class="py-2">Estatus</th>
                            <th class="py-2 w-[360px]">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr class="border-b">
                                <td data-label="Fecha" class="py-2 whitespace-nowrap">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                <td data-label="Referencia" class="py-2 whitespace-nowrap font-medium">{{ $transaction->reference ?: '-' }}</td>
                                <td data-label="Tipo" class="py-2 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded text-xs {{ $transaction->type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $transaction->type_label }}
                                    </span>
                                </td>
                                <td data-label="Alcance" class="py-2 whitespace-nowrap">{{ $transaction->scope_label }}</td>
                                <td data-label="Cliente" class="py-2">{{ $transaction->client?->full_name ?? 'Sin cliente' }}</td>
                                <td data-label="Evento" class="py-2">{{ $transaction->event?->title ?? '-' }}</td>
                                <td data-label="Categoría" class="py-2">{{ $transaction->category ?? '-' }}</td>
                                <td data-label="Proveedor / concepto" class="py-2">
                                    @if($transaction->type === 'expense')
                                        {{ $transaction->supplier?->name ?? '-' }}<br><span class="text-xs text-gray-500">{{ $transaction->expenseConcept?->name ?? '-' }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td data-label="Monto" class="py-2 text-right whitespace-nowrap {{ $transaction->type === 'income' ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $transaction->type === 'expense' ? '-' : '' }}${{ number_format($transaction->amount, 2) }}
                                </td>
                                <td data-label="Estatus" class="py-2 whitespace-nowrap">{{ $transaction->status_label }}</td>
                                <td data-label="Acciones" class="py-2">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('transactions.show', $transaction) }}" style="display:inline-flex;align-items:center;border-radius:9999px;background:#eff6ff;color:#1d4ed8;padding:6px 12px;font-size:12px;font-weight:700;text-decoration:none;">
                                            Ver recibo
                                        </a>
                                        <a href="{{ route('transactions.pdf', $transaction) }}" style="display:inline-flex;align-items:center;border-radius:9999px;background:#243834;color:#ffffff !important;padding:6px 12px;font-size:12px;font-weight:700;text-decoration:none;">
                                            PDF
                                        </a>
                                        @if($transaction->receipt_token)
                                            <a href="{{ route('receipts.public.show', $transaction->receipt_token) }}" target="_blank" style="display:inline-flex;align-items:center;border-radius:9999px;background:#ecfdf5;color:#047857;padding:6px 12px;font-size:12px;font-weight:700;text-decoration:none;">
                                                Validar
                                            </a>
                                        @endif
                                        @if($transaction->proof_file_path)
                                            <a href="{{ route('transactions.proof', $transaction) }}" class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">Comprobante</a>
                                        @endif
                                        @if($transaction->status !== \App\Models\Transaction::STATUS_CANCELLED)
                                            <a href="{{ route('transactions.edit', $transaction) }}" style="display:inline-flex;align-items:center;border-radius:9999px;background:#fffbeb;color:#b45309;padding:6px 12px;font-size:12px;font-weight:700;text-decoration:none;">Editar</a>
                                            <form action="{{ route('transactions.cancel', $transaction) }}" method="POST" onsubmit="return confirm('¿Cancelar este movimiento? Se conservará para auditoría y dejará de afectar las cifras.')">
                                                @csrf @method('PATCH')
                                                <button type="submit" style="display:inline-flex;align-items:center;border-radius:9999px;background:#fef2f2;color:#b91c1c;padding:6px 12px;font-size:12px;font-weight:700;text-decoration:none;border:none;">Cancelar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="py-4">No hay movimientos registrados.</td>
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
