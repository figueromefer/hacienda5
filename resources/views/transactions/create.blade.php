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

                <form
                    action="{{ route('transactions.store') }}"
                    method="POST"
                    class="space-y-4"
                    x-data='receiptEmailFields(
                        @json(old('type', $selectedType ?? 'income')),
                        @json(old('status', 'paid')),
                        @json(old('receipt_to', $suggestedRecipients->implode(', '))),
                        @json(old('receipt_cc', '')),
                        @json($clientRecipientMap),
                        @json($eventRecipientMap)
                    )'
                >
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label>Tipo</label>
                            <select name="type" class="w-full border rounded" x-model="type">
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
                        <select name="client_id" class="w-full border rounded" @change="addSuggestions(clientMap[$event.target.value] || [])">
                            <option value="">Selecciona un cliente</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" @selected((string) old('client_id', $selectedEvent?->client_id) === (string) $client->id)>{{ $client->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label>Evento</label>
                        <select name="event_id" class="w-full border rounded" @change="addSuggestions(eventMap[$event.target.value] || [])">
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
                            <select name="status" class="w-full border rounded" x-model="status">
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

                    <section
                        x-cloak
                        x-show="type === 'income' && status === 'paid'"
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 space-y-4"
                    >
                        <div>
                            <h3 class="font-semibold text-emerald-900">Enviar recibo por correo</h3>
                            <p class="mt-1 text-sm text-emerald-800">Se sugieren los correos relacionados. Puedes quitarlos o agregar varios separados por coma.</p>
                        </div>

                        <div>
                            <label for="receipt_to" class="block font-medium text-gray-800">Para</label>
                            <textarea id="receipt_to" name="receipt_to" x-model="to" rows="2" class="mt-1 w-full border rounded" placeholder="cliente@gmail.com, familiar@gmail.com"></textarea>
                            @error('receipt_to')<div class="mt-1 text-sm text-red-600">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label for="receipt_cc" class="block font-medium text-gray-800">CC opcional</label>
                            <textarea id="receipt_cc" name="receipt_cc" x-model="cc" rows="2" class="mt-1 w-full border rounded" placeholder="coordinacion@empresa.com"></textarea>
                            @error('receipt_cc')<div class="mt-1 text-sm text-red-600">{{ $message }}</div>@enderror
                        </div>

                        <p class="text-xs text-emerald-800">Se agregará copia institucional a info@haciendacinco.mx sin duplicarla. Si dejas ambos campos vacíos, el movimiento se guardará sin enviar correo.</p>
                    </section>

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

@push('scripts')
    <script>
        function receiptEmailFields(type, status, to, cc, clientMap, eventMap) {
            return {
                type,
                status,
                to,
                cc,
                clientMap,
                eventMap,
                addSuggestions(suggestions) {
                    const current = this.to.split(/[,;\n]+/).map(email => email.trim()).filter(Boolean);
                    const known = new Set(current.map(email => email.toLowerCase()));

                    suggestions.forEach(email => {
                        if (email && !known.has(email.toLowerCase())) {
                            current.push(email);
                            known.add(email.toLowerCase());
                        }
                    });

                    this.to = current.join(', ');
                },
            };
        }
    </script>
@endpush
