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
                <form method="GET" action="{{ route('transactions.index') }}" class="grid grid-cols-1 gap-4 items-end md:grid-cols-2 xl:grid-cols-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block mb-1 text-sm">Buscar</label>
                        <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Referencia, cliente, evento, cotización, proveedor, estado..." class="w-full rounded border-gray-300">
                    </div>
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
                            @foreach(\App\Support\DomainLabels::TRANSACTION_STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2 md:col-span-2 xl:justify-end">
                        <button class="px-4 py-2 bg-black text-white rounded">Filtrar</button>
                        <a href="{{ route('transactions.index') }}" class="px-4 py-2 bg-gray-200 rounded">Limpiar</a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow rounded p-4 sm:p-6">
                <table class="responsive-table w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Fecha</th>
                            <th class="py-2">Referencia</th>
                            <th class="py-2">Tipo / alcance</th>
                            <th class="py-2">Cliente / evento / cotización</th>
                            <th class="py-2">Proveedor / concepto</th>
                            <th class="py-2">Método / notas</th>
                            <th class="py-2 text-right">Monto</th>
                            <th class="py-2">Estatus</th>
                            <th class="w-28 py-2 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr class="border-b">
                                <td data-label="Fecha" class="py-2 whitespace-nowrap">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                <td data-label="Referencia" class="py-2 whitespace-nowrap font-medium">{{ $transaction->reference ?: '-' }}</td>
                                <td data-label="Tipo / alcance" class="py-2 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded text-xs {{ $transaction->type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $transaction->type_label }}
                                    </span>
                                    <div class="mt-1 text-xs text-gray-500">{{ $transaction->scope_label }}</div>
                                </td>
                                <td data-label="Cliente / evento / cotización" class="py-2">
                                    <div>{{ $transaction->client?->full_name ?? 'Sin cliente' }}</div>
                                    <div class="text-xs text-gray-500">{{ $transaction->event?->title ?? 'Sin evento' }}</div>
                                    @if($transaction->quotation)<div class="text-xs font-medium text-gray-600">{{ $transaction->quotation->folio }}</div>@endif
                                </td>
                                <td data-label="Proveedor / concepto" class="py-2">
                                    @if($transaction->type === 'expense')
                                        {{ $transaction->supplier?->name ?? '-' }}<br><span class="text-xs text-gray-500">{{ $transaction->expenseConcept?->name ?? '-' }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td data-label="Método / notas" class="py-2">
                                    <div>{{ $transaction->method_label }}</div>
                                    <div class="max-w-xs truncate text-xs text-gray-500" title="{{ $transaction->notes }}">{{ $transaction->notes ?: 'Sin notas' }}</div>
                                </td>
                                <td data-label="Monto" class="py-2 text-right whitespace-nowrap {{ $transaction->type === 'income' ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $transaction->type === 'expense' ? '-' : '' }}${{ number_format($transaction->amount, 2) }}
                                </td>
                                <td data-label="Estatus" class="py-2 whitespace-nowrap"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $transaction->status_classes }}">{{ $transaction->status_label }}</span></td>
                                <td data-label="Acciones" class="py-2 text-right">
                                    <x-dropdown align="right" width="56">
                                        <x-slot name="trigger">
                                            <button type="button" class="inline-flex min-h-11 items-center gap-2 rounded-lg border bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm">Acciones <span aria-hidden="true">⌄</span></button>
                                        </x-slot>
                                        <x-slot name="content">
                                            <x-dropdown-link :href="route('transactions.show', ['transaction' => $transaction, 'origin' => 'transactions'])">Ver recibo</x-dropdown-link>
                                            <x-dropdown-link :href="route('transactions.pdf', $transaction)">Descargar PDF</x-dropdown-link>
                                        @if($transaction->receipt_token)
                                                <x-dropdown-link :href="route('receipts.public.show', $transaction->receipt_token)" target="_blank" rel="noopener noreferrer">Validar</x-dropdown-link>
                                        @endif
                                        @if($transaction->proof_file_path)
                                                <x-dropdown-link :href="route('transactions.proof', $transaction)" target="_blank" rel="noopener noreferrer">Ver comprobante</x-dropdown-link>
                                        @endif
                                        @if($transaction->status !== \App\Models\Transaction::STATUS_CANCELLED)
                                                <x-dropdown-link :href="route('transactions.edit', $transaction)">Editar</x-dropdown-link>
                                                <form action="{{ route('transactions.cancel', $transaction) }}" method="POST" onsubmit="return confirm('¿Cancelar este movimiento? Se conservará para auditoría y dejará de afectar las cifras.')">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="block min-h-11 w-full px-4 py-2 text-left text-sm font-medium text-red-700 hover:bg-red-50">Cancelar</button>
                                                </form>
                                        @endif
                                        </x-slot>
                                    </x-dropdown>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-6 text-center text-gray-500">{{ $search !== '' ? 'No se encontraron movimientos.' : 'No hay movimientos registrados.' }}</td>
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
