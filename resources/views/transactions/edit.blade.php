<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar movimiento</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
                        <div class="font-semibold mb-2">No se pudo actualizar el movimiento.</div>
                        <ul class="list-disc pl-5 text-sm space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('transactions.update', $transaction) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Referencia inmutable</div>
                        <div class="mt-1 font-mono font-semibold text-gray-800">{{ $transaction->reference ?: 'Sin referencia histórica' }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label>Tipo</label>
                            <select name="type" class="w-full border rounded">
                                <option value="income" @selected(old('type', $transaction->type) === 'income')>Ingreso</option>
                                <option value="expense" @selected(old('type', $transaction->type) === 'expense')>Gasto</option>
                            </select>
                        </div>
                        <div>
                            <label>Alcance</label>
                            <select name="scope" class="w-full border rounded">
                                <option value="event" @selected(old('scope', $transaction->scope) === 'event')>Evento</option>
                                <option value="operation" @selected(old('scope', $transaction->scope) === 'operation')>Operación</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label>Cliente</label>
                        <select name="client_id" class="w-full border rounded">
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" @selected((string) old('client_id', $transaction->client_id) === (string) $client->id)>{{ $client->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label>Evento</label>
                        <select name="event_id" class="w-full border rounded">
                            <option value="">Sin evento</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" @selected((string) old('event_id', $transaction->event_id) === (string) $event->id)>{{ $event->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label>Fecha</label>
                            <input type="date" name="transaction_date" class="w-full border rounded" value="{{ old('transaction_date', $transaction->transaction_date?->format('Y-m-d')) }}">
                        </div>
                        <div>
                            <label>Monto</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="w-full border rounded" value="{{ old('amount', $transaction->amount) }}">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label>Método</label>
                            <select name="method" class="w-full border rounded">
                                @foreach(['transfer' => 'Transferencia', 'cash' => 'Efectivo', 'card' => 'Tarjeta', 'other' => 'Otro'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('method', $transaction->method) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>Estatus</label>
                            <select name="status" class="w-full border rounded">
                                <option value="paid" @selected(old('status', $transaction->status) === 'paid')>Pagado</option>
                                <option value="pending" @selected(old('status', $transaction->status) === 'pending')>Pendiente</option>
                                <option value="cancelled" @selected(old('status', $transaction->status) === 'cancelled')>Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label>Categoría</label>
                        <input type="text" name="category" class="w-full border rounded" value="{{ old('category', $transaction->category) }}">
                    </div>

                    <div>
                        <label>Notas</label>
                        <textarea name="notes" class="w-full border rounded">{{ old('notes', $transaction->notes) }}</textarea>
                    </div>

                    <div class="flex flex-col-reverse md:flex-row gap-2">
                        <a href="{{ route('transactions.index') }}" class="w-full md:w-auto px-4 py-3 md:py-2 bg-gray-200 text-center rounded">Cancelar</a>
                        <button class="w-full md:w-auto px-4 py-3 md:py-2 bg-black text-white rounded">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
