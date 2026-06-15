<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $event->title }}
            </h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('events.contracts.create', $event) }}" style="display:inline-flex;align-items:center;border-radius:10px;background:#243834;color:#ffffff !important;padding:10px 16px;font-weight:700;text-decoration:none;">
                    Generar contrato
                </a>
                <a href="{{ route('events.edit', $event) }}" class="px-4 py-2 bg-black text-white rounded">
                    Editar evento
                </a>
            </div>
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
                <div class="bg-white shadow rounded p-5"><div class="text-sm text-gray-500">Ingresos</div><div class="text-xl font-bold text-green-700">${{ number_format($income, 2) }}</div></div>
                <div class="bg-white shadow rounded p-5"><div class="text-sm text-gray-500">Gastos</div><div class="text-xl font-bold text-red-700">${{ number_format($expenses, 2) }}</div></div>
                <div class="bg-white shadow rounded p-5"><div class="text-sm text-gray-500">Pendiente por cobrar</div><div class="text-xl font-bold text-yellow-600">${{ number_format($pendingIncome, 2) }}</div></div>
                <div class="bg-white shadow rounded p-5"><div class="text-sm text-gray-500">Balance</div><div class="text-xl font-bold {{ $balance >= 0 ? 'text-green-700' : 'text-red-700' }}">${{ number_format($balance, 2) }}</div></div>
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
                            <div class="border rounded p-3 flex justify-between items-center gap-4">
                                <div>
                                    <div class="font-semibold">{{ $transaction->type === 'income' ? 'Ingreso' : 'Gasto' }} - ${{ number_format($transaction->amount, 2) }}</div>
                                    <div class="text-sm text-gray-600">{{ $transaction->transaction_date->format('d/m/Y') }} · {{ $transaction->category ?? 'Sin categoría' }}</div>
                                </div>
                                <div class="text-sm {{ $transaction->type === 'income' ? 'text-green-600' : 'text-red-600' }}">{{ $transaction->status }}</div>
                            </div>
                        @empty
                            <p class="text-gray-500">No hay movimientos registrados.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white shadow rounded p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Recibos emitidos</h3>
                    </div>
                    <div class="space-y-3">
                        @forelse($event->transactions->sortByDesc('transaction_date') as $transaction)
                            <div class="border rounded-xl p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div>
                                    <div class="font-semibold">
                                        Recibo #{{ $transaction->id }} · {{ $transaction->type_label }} · ${{ number_format($transaction->amount, 2) }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ $transaction->transaction_date?->format('d/m/Y') }} · {{ $transaction->category ?? 'Sin categoría' }} · {{ $transaction->status }}
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('transactions.show', $transaction) }}" style="display:inline-flex;align-items:center;border-radius:9999px;background:#eff6ff;color:#1d4ed8;padding:6px 12px;font-size:12px;font-weight:700;text-decoration:none;">Ver recibo</a>
                                    <a href="{{ route('transactions.pdf', $transaction) }}" style="display:inline-flex;align-items:center;border-radius:9999px;background:#243834;color:#ffffff !important;padding:6px 12px;font-size:12px;font-weight:700;text-decoration:none;">PDF</a>
                                    @if($transaction->receipt_token)
                                        <a href="{{ route('receipts.public.show', $transaction->receipt_token) }}" target="_blank" style="display:inline-flex;align-items:center;border-radius:9999px;background:#ecfdf5;color:#047857;padding:6px 12px;font-size:12px;font-weight:700;text-decoration:none;">Validar</a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="border border-dashed rounded-xl p-6 text-center text-gray-500">No hay recibos generados para este evento.</div>
                        @endforelse
                    </div>
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
                            <div class="min-w-0"><div class="font-semibold truncate">{{ $document->original_name }}</div><div class="text-sm text-gray-500">{{ $document->category }} · {{ number_format(($document->file_size ?? 0) / 1024, 1) }} KB</div>@if($document->notes)<div class="text-sm text-gray-600 mt-1">{{ $document->notes }}</div>@endif</div>
                            <div class="flex items-center gap-2 shrink-0"><a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white transition" title="Ver">👁</a><form action="{{ route('documents.destroy', $document) }}" method="POST" onsubmit="return confirm('¿Eliminar este documento?')">@csrf @method('DELETE')<button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-red-50 text-red-700 hover:bg-red-600 hover:text-white transition" title="Eliminar">🗑</button></form></div>
                        </div>
                    @empty
                        <div class="border border-dashed rounded-xl p-6 text-center text-gray-500">No hay documentos cargados para este evento.</div>
                    @endforelse
                </div>
            </div>

            <div class="bg-white shadow rounded p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Tareas del evento</h3>
                    <span class="text-sm text-gray-500">{{ $event->tasks->where('status', 'done')->count() }}/{{ $event->tasks->count() }} completadas</span>
                </div>

                <form action="{{ route('events.tasks.store', $event) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 rounded-xl border bg-gray-50 p-4">
                    @csrf
                    <div class="md:col-span-2"><label class="block text-sm mb-1">Título</label><input type="text" name="title" class="w-full border rounded" required></div>
                    <div><label class="block text-sm mb-1">Fecha límite</label><input type="datetime-local" name="due_date" class="w-full border rounded"></div>
                    <div><label class="block text-sm mb-1">Responsable</label><select name="assigned_to" class="w-full border rounded"><option value="">Sin asignar</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
                    <div><label class="block text-sm mb-1">Estatus</label><select name="status" class="w-full border rounded"><option value="pending">Pendiente</option><option value="done">Hecha</option><option value="cancelled">Cancelada</option></select></div>
                    <div class="md:col-span-2"><label class="block text-sm mb-1">Notas</label><input type="text" name="notes" class="w-full border rounded"></div>
                    <div class="md:col-span-1 flex items-end"><button class="w-full px-4 py-2 bg-black text-white rounded">Agregar tarea</button></div>
                </form>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    @foreach(['pending' => 'Pendientes', 'done' => 'Completadas', 'cancelled' => 'Canceladas'] as $status => $label)
                        <div class="rounded-xl border bg-gray-50 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold text-gray-800">{{ $label }}</h4>
                                <span class="rounded-full bg-white px-2 py-1 text-xs text-gray-500 border">{{ $event->tasks->where('status', $status)->count() }}</span>
                            </div>
                            <div class="space-y-3">
                                @forelse($event->tasks->where('status', $status) as $task)
                                    <div class="rounded-xl border bg-white p-4 shadow-sm">
                                        <div class="font-semibold text-gray-900">{{ $task->title }}</div>
                                        <div class="mt-2 text-sm text-gray-600">Responsable: {{ $task->assignedUser?->name ?? 'Sin asignar' }}</div>
                                        <div class="text-sm text-gray-600">Fecha límite: {{ $task->due_date?->format('d/m/Y H:i') ?? '-' }}</div>
                                        @if($task->notes)<div class="mt-2 text-sm text-gray-700">{{ $task->notes }}</div>@endif
                                        <form action="{{ route('events.tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('¿Eliminar esta tarea?')" class="mt-3 text-right">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-sm text-red-600 hover:text-red-800">Eliminar</button>
                                        </form>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed p-4 text-center text-sm text-gray-500">Sin tareas</div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white shadow rounded p-6">
                <h3 class="text-lg font-semibold mb-6">Timeline del evento</h3>
                <div class="relative">
                    <div class="absolute left-4 top-0 bottom-0 w-px bg-gray-200"></div>
                    <div class="space-y-6">
                        @forelse($timeline as $item)
                            @php $colorClasses = match($item['color']) {'green' => 'bg-green-100 text-green-700 ring-green-200','red' => 'bg-red-100 text-red-700 ring-red-200','blue' => 'bg-blue-100 text-blue-700 ring-blue-200', default => 'bg-gray-100 text-gray-700 ring-gray-200'}; @endphp
                            <div class="relative flex gap-4 pl-12">
                                <div class="absolute left-0 top-1 flex h-8 w-8 items-center justify-center rounded-full ring-4 {{ $colorClasses }}">@if($item['type'] === 'Ingreso')$@elseif($item['type'] === 'Gasto')-@elseif($item['type'] === 'Documento')📎@else📝@endif</div>
                                <div class="flex-1 rounded-xl border bg-gray-50 p-4"><div class="flex flex-col md:flex-row md:items-center md:justify-between gap-1"><div><div class="text-sm font-semibold text-gray-900">{{ $item['title'] }}</div><div class="text-sm text-gray-600 mt-1">{{ $item['description'] }}</div></div><div class="text-xs text-gray-500 whitespace-nowrap">{{ $item['date']?->format('d/m/Y H:i') }}</div></div><div class="mt-2 inline-flex rounded-full bg-white px-2 py-1 text-xs text-gray-500 border">{{ $item['type'] }}</div></div>
                            </div>
                        @empty
                            <div class="border border-dashed rounded-xl p-6 text-center text-gray-500">Todavía no hay actividad registrada en este evento.</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
