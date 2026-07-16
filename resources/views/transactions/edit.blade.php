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

                <form action="{{ route('transactions.update', $transaction) }}" method="POST" enctype="multipart/form-data" class="space-y-4" x-data="transactionEditForm(@js(old('type', $transaction->type)), @js((string) old('client_id', $transaction->client_id)), @js((string) old('event_id', $transaction->event_id)), @js((string) old('quotation_id', $transaction->quotation_id)))">
                    @csrf
                    @method('PUT')

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Referencia inmutable</div>
                        <div class="mt-1 font-mono font-semibold text-gray-800">{{ $transaction->reference ?: 'Sin referencia histórica' }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label>Tipo</label>
                            <select name="type" class="w-full border rounded" x-model="type">
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
                        <label for="client_id">Cliente</label>
                        <select id="client_id" name="client_id" class="w-full border rounded" x-model="clientId" @change="ensureCoherentSelection()">
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" @selected((string) old('client_id', $transaction->client_id) === (string) $client->id)>{{ $client->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="event_id">Evento</label>
                        <select id="event_id" name="event_id" class="w-full border rounded" x-model="eventId" @change="ensureQuotation()">
                            <option value="">Sin evento</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" :disabled="clientId && clientId !== '{{ $event->client_id }}'" x-show="!clientId || clientId === '{{ $event->client_id }}'">{{ $event->title }}</option>
                            @endforeach
                        </select>
                        @error('event_id')<div class="mt-1 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="quotation_id">Cotización (opcional)</label>
                        <select id="quotation_id" name="quotation_id" class="w-full border rounded" x-model="quotationId">
                            <option value="">Sin cotización</option>
                            @foreach($quotations as $quotation)
                                <option value="{{ $quotation->id }}" :disabled="clientId !== '{{ $quotation->client_id }}' || eventId !== '{{ (string) $quotation->event_id }}'" x-show="clientId === '{{ $quotation->client_id }}' && eventId === '{{ (string) $quotation->event_id }}'">{{ $quotation->folio ?: 'Cotización #'.$quotation->id }}</option>
                            @endforeach
                        </select>
                        @error('quotation_id')<div class="mt-1 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label>Fecha</label>
                            <input type="date" name="transaction_date" class="w-full border rounded" value="{{ old('transaction_date', $transaction->transaction_date?->format('Y-m-d')) }}">
                        </div>
                        <div>
                            <label for="amount">Monto</label>
                            <x-money-input id="amount" name="amount" :value="old('amount', $transaction->amount)" required />
                            @error('amount')<div class="mt-1 text-sm text-red-600">{{ $message }}</div>@enderror
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
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">Estado conservado: <strong>{{ $transaction->status_label }}</strong>. El estado no se modifica desde este formulario.</div>

                    <div x-cloak x-show="type === 'expense'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label>Proveedor (opcional)</label>
                            <select name="supplier_id" class="w-full border rounded">
                                <option value="">Sin proveedor</option>
                                @if($transaction->supplier && ! $transaction->supplier->is_active)
                                    <option value="{{ $transaction->supplier->id }}" @selected((string) old('supplier_id', $transaction->supplier_id) === (string) $transaction->supplier->id)>{{ $transaction->supplier->name }} (archivado)</option>
                                @endif
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $transaction->supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>Concepto de gasto (opcional)</label>
                            <select name="expense_concept_id" class="w-full border rounded">
                                <option value="">Sin concepto</option>
                                @if($transaction->expenseConcept && ! $transaction->expenseConcept->is_active)
                                    <option value="{{ $transaction->expenseConcept->id }}" @selected((string) old('expense_concept_id', $transaction->expense_concept_id) === (string) $transaction->expenseConcept->id)>{{ $transaction->expenseConcept->name }} (archivado)</option>
                                @endif
                                @foreach($expenseConcepts as $expenseConcept)
                                    <option value="{{ $expenseConcept->id }}" @selected((string) old('expense_concept_id', $transaction->expense_concept_id) === (string) $expenseConcept->id)>{{ $expenseConcept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label>Notas</label>
                        <textarea name="notes" class="w-full border rounded">{{ old('notes', $transaction->notes) }}</textarea>
                    </div>

                    <div>
                        <label for="proof_file">{{ $transaction->proof_file_path ? 'Reemplazar comprobante' : 'Comprobante (opcional)' }}</label>
                        <input id="proof_file" name="proof_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" class="mt-1 block w-full rounded border border-gray-300 p-2">
                        <p class="mt-1 text-xs text-gray-500">PDF, JPG, PNG o WEBP. Máximo 10 MB.</p>
                        @if($transaction->proof_file_path)
                            <a href="{{ route('transactions.proof', $transaction) }}" class="mt-2 inline-flex text-sm font-medium text-blue-700 hover:underline">Ver comprobante actual</a>
                        @endif
                        @error('proof_file')<div class="mt-1 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>

                    <div class="flex flex-col-reverse md:flex-row gap-2">
                        <a x-bind:href="eventId ? @js(url('/events')).concat('/', eventId) : @js(route('transactions.index'))" class="w-full md:w-auto px-4 py-3 md:py-2 bg-gray-200 text-center rounded">Cancelar</a>
                        <button class="w-full md:w-auto px-4 py-3 md:py-2 bg-black text-white rounded">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
    <script>
        function transactionEditForm(type, clientId, eventId, quotationId) {
            return {
                type,
                clientId,
                eventId,
                quotationId,
                ensureCoherentSelection() {
                    const option = this.$root.querySelector(`#event_id option[value="${this.eventId}"]`);
                    if (option?.disabled) {
                        this.eventId = '';
                    }
                    this.ensureQuotation();
                },
                ensureQuotation() {
                    const option = this.$root.querySelector(`#quotation_id option[value="${this.quotationId}"]`);
                    if (option?.disabled) {
                        this.quotationId = '';
                    }
                },
            };
        }
    </script>
@endpush
