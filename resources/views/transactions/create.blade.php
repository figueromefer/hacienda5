<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo movimiento</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
                        <div class="font-semibold mb-2">No se pudo guardar el movimiento.</div>
                        <ul class="list-disc pl-5 text-sm space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('transactions.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label>Tipo</label>
                            <select name="type" class="w-full border rounded">
                                <option value="income" @selected(old('type', $selectedType ?? 'income') === 'income')>Ingreso</option>
                                <option value="expense" @selected(old('type', $selectedType ?? 'income') === 'expense')>Gasto</option>
                            </select>
                        </div>
                        <div>
                            <label>Alcance</label>
                            <select name="scope" class="w-full border rounded">
                                <option value="event" @selected(old('scope', 'event') === 'event')>Evento</option>
                                <option value="operation" @selected(old('scope') === 'operation')>Operación</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label>Cliente</label>
                        <select name="client_id" class="w-full border rounded">
                            <option value="">Selecciona un cliente</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" @selected((string) old('client_id', $selectedEvent?->client_id) === (string) $client->id)>{{ $client->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label>Evento</label>
                        <select name="event_id" class="w-full border rounded">
                            <option value="">Sin evento</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" @selected((string) old('event_id', $selectedEvent?->id) === (string) $event->id)>{{ $event->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label>Fecha</label>
                            <input type="date" name="transaction_date" class="w-full border rounded" value="{{ old('transaction_date', now()->format('Y-m-d')) }}">
                        </div>
                        <div>
                            <label>Monto</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="w-full border rounded" value="{{ old('amount') }}">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label>Método</label>
                            <select name="method" class="w-full border rounded">
                                <option value="transfer" @selected(old('method') === 'transfer')>Transferencia</option>
                                <option value="cash" @selected(old('method') === 'cash')>Efectivo</option>
                                <option value="card" @selected(old('method') === 'card')>Tarjeta</option>
                                <option value="other" @selected(old('method') === 'other')>Otro</option>
                            </select>
                        </div>
                        <div>
                            <label>Estatus</label>
                            <select name="status" class="w-full border rounded">
                                <option value="paid" @selected(old('status', 'paid') === 'paid')>Pagado</option>
                                <option value="pending" @selected(old('status') === 'pending')>Pendiente</option>
                                <option value="cancelled" @selected(old('status') === 'cancelled')>Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label>Categoría</label>
                        <input type="text" name="category" class="w-full border rounded" value="{{ old('category') }}">
                    </div>

                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                        La referencia se generará automáticamente al guardar, según el tipo y año del movimiento.
                    </div>

                    <div>
                        <label>Notas</label>
                        <textarea name="notes" class="w-full border rounded">{{ old('notes') }}</textarea>
                    </div>

                    <button class="w-full md:w-auto px-4 py-3 md:py-2 bg-black text-white rounded">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
