<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Gastos</h2>
            <a href="{{ route('transactions.create', ['type' => 'expense']) }}" class="inline-flex min-h-11 items-center justify-center rounded bg-black px-4 py-2 text-white">
                Nuevo gasto
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded bg-white p-5 shadow">
                    <div class="text-sm text-gray-500">Total filtrado</div>
                    <div class="text-2xl font-bold text-gray-900">${{ number_format($total, 2) }}</div>
                </div>
                <div class="rounded bg-white p-5 shadow">
                    <div class="text-sm text-gray-500">Pagado</div>
                    <div class="text-2xl font-bold text-red-700">${{ number_format($paidTotal, 2) }}</div>
                </div>
                <div class="rounded bg-white p-5 shadow">
                    <div class="text-sm text-gray-500">Pendiente</div>
                    <div class="text-2xl font-bold text-amber-700">${{ number_format($pendingTotal, 2) }}</div>
                </div>
            </div>

            <div class="rounded bg-white p-4 shadow sm:p-6">
                <form method="GET" action="{{ route('expenses.index') }}" class="grid grid-cols-1 items-end gap-4 md:grid-cols-2 xl:grid-cols-6">
                    <div class="md:col-span-2 xl:col-span-1">
                        <label for="search" class="mb-1 block text-sm">Texto</label>
                        <input id="search" name="search" type="search" value="{{ request('search') }}" placeholder="Referencia, cliente..." class="w-full rounded border-gray-300">
                    </div>
                    <div>
                        <label for="from" class="mb-1 block text-sm">Desde</label>
                        <input id="from" name="from" type="date" value="{{ request('from') }}" class="w-full rounded border-gray-300">
                    </div>
                    <div>
                        <label for="to" class="mb-1 block text-sm">Hasta</label>
                        <input id="to" name="to" type="date" value="{{ request('to') }}" class="w-full rounded border-gray-300">
                    </div>
                    <div>
                        <label for="supplier_id" class="mb-1 block text-sm">Proveedor</label>
                        <select id="supplier_id" name="supplier_id" class="w-full rounded border-gray-300">
                            <option value="">Todos</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="expense_concept_id" class="mb-1 block text-sm">Concepto</label>
                        <select id="expense_concept_id" name="expense_concept_id" class="w-full rounded border-gray-300">
                            <option value="">Todos</option>
                            @foreach($expenseConcepts as $concept)
                                <option value="{{ $concept->id }}" @selected((string) request('expense_concept_id') === (string) $concept->id)>{{ $concept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status" class="mb-1 block text-sm">Estado</label>
                        <select id="status" name="status" class="w-full rounded border-gray-300">
                            <option value="">Todos</option>
                            <option value="pending" @selected(request('status') === 'pending')>Pendiente</option>
                            <option value="paid" @selected(request('status') === 'paid')>Pagado</option>
                            <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelado</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row md:col-span-2 xl:col-span-6 xl:justify-end">
                        <button class="min-h-11 rounded bg-black px-4 py-2 text-white">Filtrar</button>
                        <a href="{{ route('expenses.index') }}" class="inline-flex min-h-11 items-center justify-center rounded bg-gray-200 px-4 py-2">Limpiar</a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto rounded bg-white p-4 shadow sm:p-6">
                <table class="responsive-table w-full min-w-[1050px] border-collapse text-left">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Fecha</th>
                            <th class="py-2">Referencia</th>
                            <th class="py-2">Cliente / evento</th>
                            <th class="py-2">Proveedor</th>
                            <th class="py-2">Concepto</th>
                            <th class="py-2">Estado</th>
                            <th class="py-2 text-right">Monto</th>
                            <th class="py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr class="border-b">
                                <td data-label="Fecha" class="whitespace-nowrap py-3">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                <td data-label="Referencia" class="py-3 font-medium">{{ $transaction->reference ?: '-' }}</td>
                                <td data-label="Cliente / evento" class="py-3">{{ $transaction->client->full_name }}<br><span class="text-xs text-gray-500">{{ $transaction->event?->title ?? 'Operación' }}</span></td>
                                <td data-label="Proveedor" class="py-3">{{ $transaction->supplier?->name ?? '-' }}</td>
                                <td data-label="Concepto" class="py-3">{{ $transaction->expenseConcept?->name ?? $transaction->category ?? '-' }}</td>
                                <td data-label="Estado" class="py-3">{{ ['pending' => 'Pendiente', 'paid' => 'Pagado', 'cancelled' => 'Cancelado'][$transaction->status] ?? $transaction->status }}</td>
                                <td data-label="Monto" class="whitespace-nowrap py-3 text-right font-semibold text-red-700">${{ number_format($transaction->amount, 2) }}</td>
                                <td data-label="Acciones" class="py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('transactions.show', $transaction) }}" class="rounded-full bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700">Ver</a>
                                        <a href="{{ route('transactions.edit', $transaction) }}" class="rounded-full bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700">Editar</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-6 text-center text-gray-500">No hay gastos con los filtros seleccionados.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">{{ $transactions->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
