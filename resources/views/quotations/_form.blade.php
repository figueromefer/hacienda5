@php
    $selectedClientId = (string) old('client_id', $quotation->client_id ?? '');
    $selectedEventId = (string) old('event_id', $quotation->event_id ?? '');
    $formItems = old('items', isset($quotation)
        ? $quotation->items->map(fn ($item) => [
            'service_id' => $item->service_id,
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
        ])->all()
        : [['service_id' => null, 'description' => '', 'quantity' => 1, 'unit_price' => '0.00']]);
    $eventPayload = $events->map(fn ($event) => [
        'id' => (string) $event->id,
        'client_id' => (string) $event->client_id,
        'title' => $event->title,
        'date' => $event->event_date?->format('d/m/Y'),
        'type' => $event->event_type,
        'guests' => $event->guest_count,
        'status' => $event->status_label,
        'budget' => number_format((float) ($event->budget_estimate ?? 0), 2),
    ])->values();
@endphp

@if($errors->any())
    <div class="rounded border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
        <p class="font-semibold">Revisa la información indicada.</p>
        <ul class="mt-2 list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label for="client_id" class="mb-1 block">Cliente</label>
        <select name="client_id" id="client_id" class="w-full rounded border-gray-300" required>
            <option value="">Selecciona</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" @selected($selectedClientId === (string) $client->id)>{{ $client->full_name }}</option>
            @endforeach
        </select>
        @error('client_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="event_id" class="mb-1 block">Evento</label>
        <select name="event_id" id="event_id" class="w-full rounded border-gray-300">
            <option value="">Sin evento</option>
        </select>
        @error('event_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="status" class="mb-1 block">Estatus</label>
        <select name="status" id="status" class="w-full rounded border-gray-300" required>
            @foreach(\App\Support\DomainLabels::QUOTATION_STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $quotation->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="valid_until" class="mb-1 block">Válida hasta</label>
        <input type="date" name="valid_until" id="valid_until" class="w-full rounded border-gray-300" value="{{ old('valid_until', isset($quotation) ? $quotation->valid_until?->format('Y-m-d') : '') }}">
        @error('valid_until')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label for="notes" class="mb-1 block">Notas</label>
    <textarea name="notes" id="notes" class="w-full rounded border-gray-300">{{ old('notes', $quotation->notes ?? '') }}</textarea>
    @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<section id="event-summary" class="rounded-lg border border-emerald-200 bg-emerald-50 p-4" aria-live="polite">
    <h3 class="font-semibold text-emerald-950">Información del evento</h3>
    <p id="event-empty" class="mt-2 text-sm text-emerald-900">Sin evento seleccionado.</p>
    <dl id="event-details" class="mt-3 hidden grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
        <div><dt class="font-medium text-emerald-950">Nombre</dt><dd data-event-field="title"></dd></div>
        <div><dt class="font-medium text-emerald-950">Fecha</dt><dd data-event-field="date"></dd></div>
        <div><dt class="font-medium text-emerald-950">Tipo</dt><dd data-event-field="type"></dd></div>
        <div><dt class="font-medium text-emerald-950">Invitados</dt><dd data-event-field="guests"></dd></div>
        <div><dt class="font-medium text-emerald-950">Estado</dt><dd data-event-field="status"></dd></div>
        <div><dt class="font-medium text-emerald-950">Presupuesto estimado</dt><dd data-event-field="budget"></dd></div>
    </dl>
</section>

<section>
    <div class="mb-3 flex items-center justify-between gap-3">
        <h3 class="text-lg font-semibold">Items</h3>
        <button type="button" id="add-item" class="rounded bg-black px-3 py-2 text-white">Agregar item</button>
    </div>

    <div class="overflow-x-auto">
        <table class="responsive-table w-full border" id="items-table">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border p-2">Servicio</th>
                    <th class="border p-2">Descripción</th>
                    <th class="border p-2">Cantidad</th>
                    <th class="border p-2">Precio unitario</th>
                    <th class="border p-2">Total</th>
                    <th class="border p-2">Acción</th>
                </tr>
            </thead>
            <tbody id="items-body">
                @foreach($formItems as $i => $item)
                    @include('quotations._item-row', ['index' => $i, 'item' => $item])
                @endforeach
            </tbody>
        </table>
    </div>
    @error('items')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</section>

<div class="grid max-w-xl grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label for="discount_type" class="mb-1 block">Tipo de descuento</label>
        <select id="discount_type" name="discount_type" class="w-full rounded border-gray-300">
            <option value="amount" @selected(old('discount_type', $quotation->discount_type ?? 'amount') === 'amount')>Monto</option>
            <option value="percentage" @selected(old('discount_type', $quotation->discount_type ?? 'amount') === 'percentage')>Porcentaje</option>
        </select>
        @error('discount_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
    <label for="discount" class="mb-1 block">Descuento</label>
    <x-money-input
        name="discount"
        id="discount"
        :value="old('discount', $quotation->discount ?? '0.00')"
        min="0"
        aria-describedby="discount-error"
        x-on:input="$dispatch('quotation-money-change')"
    />
    @error('discount')<p id="discount-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="rounded bg-gray-50 p-4">
        <div class="text-sm text-gray-600">Subtotal</div>
        <div class="text-xl font-bold" id="subtotal-display">$ 0.00</div>
    </div>
    <div class="rounded bg-gray-50 p-4">
        <div class="text-sm text-gray-600">Descuento</div>
        <div class="text-xl font-bold" id="discount-display">$ 0.00</div>
    </div>
    <div class="rounded bg-gray-50 p-4">
        <div class="text-sm text-gray-600">Total</div>
        <div class="text-xl font-bold" id="total-display">$ 0.00</div>
    </div>
</div>

<div class="flex flex-wrap gap-3">
    <button class="rounded bg-black px-4 py-2 text-white">{{ $submitLabel }}</button>
    <a href="{{ route('quotations.index') }}" class="rounded bg-gray-200 px-4 py-2 text-gray-900">Cancelar</a>
</div>

<template id="quotation-item-template">
    @include('quotations._item-row', [
        'index' => '__INDEX__',
        'item' => ['service_id' => null, 'description' => '', 'quantity' => 1, 'unit_price' => '0.00'],
    ])
</template>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const events = @json($eventPayload);
                const selectedEventId = @json($selectedEventId);
                const clientSelect = document.getElementById('client_id');
                const eventSelect = document.getElementById('event_id');
                const itemsBody = document.getElementById('items-body');
                const money = value => Number(String(value ?? '').replace(/[$,\s]/g, '')) || 0;
                const formatMoney = value => '$ ' + new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(value);
                let nextIndex = {{ count($formItems) }};

                function selectedEvent() {
                    return events.find(event => event.id === eventSelect.value);
                }

                function renderEventSummary() {
                    const event = selectedEvent();
                    document.getElementById('event-empty').classList.toggle('hidden', Boolean(event));
                    const details = document.getElementById('event-details');
                    details.classList.toggle('hidden', !event);
                    details.classList.toggle('grid', Boolean(event));

                    if (!event) return;

                    Object.entries({
                        title: event.title,
                        date: event.date || 'Sin fecha',
                        type: event.type || 'Sin tipo',
                        guests: event.guests ?? 'Sin definir',
                        status: event.status,
                        budget: formatMoney(money(event.budget)),
                    }).forEach(([field, value]) => {
                        details.querySelector('[data-event-field="' + field + '"]').textContent = value;
                    });
                }

                function renderEventOptions(preferredEventId = '') {
                    const allowedEvents = events.filter(event => event.client_id === clientSelect.value);
                    eventSelect.replaceChildren(new Option('Sin evento', ''));
                    allowedEvents.forEach(event => {
                        eventSelect.add(new Option(event.title + ' - ' + (event.date || 'Sin fecha'), event.id));
                    });
                    eventSelect.value = allowedEvents.some(event => event.id === preferredEventId) ? preferredEventId : '';
                    renderEventSummary();
                }

                function recalculateTotals() {
                    let subtotal = 0;
                    itemsBody.querySelectorAll('tr').forEach(row => {
                        const quantity = Number(row.querySelector('.quantity-input')?.value || 0);
                        const price = money(row.querySelector('.price-input')?.value);
                        const total = quantity * price;
                        row.querySelector('.item-total').textContent = formatMoney(total);
                        subtotal += total;
                    });
                    const discount = money(document.getElementById('discount')?.value);
                    const discountType = document.getElementById('discount_type')?.value;
                    const effectiveDiscount = discountType === 'percentage' ? subtotal * discount / 100 : discount;
                    document.getElementById('subtotal-display').textContent = formatMoney(subtotal);
                    document.getElementById('discount-display').textContent = formatMoney(discount);
                    document.getElementById('total-display').textContent = formatMoney(Math.max(subtotal - effectiveDiscount, 0));
                }

                function bindRow(row) {
                    row.querySelector('.service-select')?.addEventListener('change', event => {
                        const option = event.target.selectedOptions[0];
                        const priceInput = row.querySelector('.price-input');
                        if (option.dataset.price) {
                            priceInput.value = option.dataset.price;
                            priceInput.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                        if (option.dataset.name && !row.querySelector('.description-input').value) {
                            row.querySelector('.description-input').value = option.dataset.name;
                        }
                        recalculateTotals();
                    });
                    row.querySelector('.quantity-input')?.addEventListener('input', recalculateTotals);
                    row.querySelector('.price-input')?.addEventListener('input', recalculateTotals);
                    row.querySelector('.remove-item')?.addEventListener('click', () => {
                        if (itemsBody.querySelectorAll('tr').length > 1) {
                            row.remove();
                            recalculateTotals();
                        }
                    });
                }

                clientSelect.addEventListener('change', () => renderEventOptions());
                eventSelect.addEventListener('change', renderEventSummary);
                window.addEventListener('quotation-money-change', recalculateTotals);
                document.getElementById('discount_type').addEventListener('change', recalculateTotals);
                document.getElementById('add-item').addEventListener('click', () => {
                    const template = document.getElementById('quotation-item-template').innerHTML.replaceAll('__INDEX__', nextIndex++);
                    itemsBody.insertAdjacentHTML('beforeend', template);
                    bindRow(itemsBody.lastElementChild);
                    recalculateTotals();
                });
                itemsBody.querySelectorAll('tr').forEach(bindRow);
                renderEventOptions(selectedEventId);
                recalculateTotals();
            });
        </script>
    @endpush
@endonce
