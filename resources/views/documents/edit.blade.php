<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar pago</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <form action="{{ route('payments.update', $payment) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block mb-1">Cliente</label>
                        <select name="client_id" class="w-full border rounded">
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" @selected($payment->client_id == $client->id)>
                                    {{ $client->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1">Evento</label>
                        <select name="event_id" class="w-full border rounded">
                            <option value="">Sin evento</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" @selected($payment->event_id == $event->id)>
                                    {{ $event->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1">Cotización</label>
                        <select name="quotation_id" class="w-full border rounded">
                            <option value="">Sin cotización</option>
                            @foreach($quotations as $quotation)
                                <option value="{{ $quotation->id }}" @selected($payment->quotation_id == $quotation->id)>
                                    {{ $quotation->folio }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1">Fecha de pago</label>
                            <input type="date" name="payment_date" class="w-full border rounded" value="{{ old('payment_date', $payment->payment_date->format('Y-m-d')) }}">
                        </div>
                        <div>
                            <label class="block mb-1">Monto</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="w-full border rounded" value="{{ old('amount', $payment->amount) }}">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1">Método</label>
                            <select name="method" class="w-full border rounded">
                                <option value="transfer" @selected($payment->method === 'transfer')>Transferencia</option>
                                <option value="cash" @selected($payment->method === 'cash')>Efectivo</option>
                                <option value="card" @selected($payment->method === 'card')>Tarjeta</option>
                                <option value="other" @selected($payment->method === 'other')>Otro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-1">Estatus</label>
                            <select name="status" class="w-full border rounded">
                                <option value="paid" @selected($payment->status === 'paid')>Pagado</option>
                                <option value="pending" @selected($payment->status === 'pending')>Pendiente</option>
                                <option value="cancelled" @selected($payment->status === 'cancelled')>Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block mb-1">Referencia</label>
                        <input type="text" name="reference" class="w-full border rounded" value="{{ old('reference', $payment->reference) }}">
                    </div>

                    <div>
                        <label class="block mb-1">Notas</label>
                        <textarea name="notes" class="w-full border rounded">{{ old('notes', $payment->notes) }}</textarea>
                    </div>

                    <button class="px-4 py-2 bg-black text-white rounded">Actualizar pago</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>