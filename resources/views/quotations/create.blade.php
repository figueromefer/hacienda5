<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nueva cotización</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <form action="{{ route('quotations.store') }}" method="POST" id="quotation-form" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1">Cliente</label>
                            <select name="client_id" class="w-full border rounded">
                                <option value="">Selecciona</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->full_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1">Evento</label>
                            <select name="event_id" class="w-full border rounded">
                                <option value="">Sin evento</option>
                                @foreach($events as $event)
                                    <option value="{{ $event->id }}">
                                        {{ $event->title }} - {{ $event->event_date->format('d/m/Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1">Estatus</label>
                            <select name="status" class="w-full border rounded">
                                <option value="draft">Draft</option>
                                <option value="sent">Enviada</option>
                                <option value="approved">Aprobada</option>
                                <option value="rejected">Rechazada</option>
                                <option value="expired">Expirada</option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1">Válida hasta</label>
                            <input type="date" name="valid_until" class="w-full border rounded">
                        </div>

                        <div>
                            <label class="block mb-1">Descuento</label>
                            <input type="number" step="0.01" min="0" name="discount" id="discount" class="w-full border rounded" value="0">
                        </div>
                    </div>

                    <div>
                        <label class="block mb-1">Notas</label>
                        <textarea name="notes" class="w-full border rounded"></textarea>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-semibold">Items</h3>
                            <button type="button" id="add-item" class="px-3 py-2 bg-black text-white rounded">
                                Agregar item
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full border" id="items-table">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="p-2 border">Servicio</th>
                                        <th class="p-2 border">Descripción</th>
                                        <th class="p-2 border">Cantidad</th>
                                        <th class="p-2 border">Precio unitario</th>
                                        <th class="p-2 border">Total</th>
                                        <th class="p-2 border">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="items-body">
                                    <tr>
                                        <td class="p-2 border">
                                            <select name="items[0][service_id]" class="w-full border rounded service-select">
                                                <option value="">Manual</option>
                                                @foreach($services as $service)
                                                    <option value="{{ $service->id }}" data-price="{{ $service->base_price }}" data-name="{{ $service->name }}">
                                                        {{ $service->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="p-2 border">
                                            <input type="text" name="items[0][description]" class="w-full border rounded description-input">
                                        </td>
                                        <td class="p-2 border">
                                            <input type="number" name="items[0][quantity]" min="1" value="1" class="w-full border rounded quantity-input">
                                        </td>
                                        <td class="p-2 border">
                                            <input type="number" step="0.01" min="0" name="items[0][unit_price]" value="0" class="w-full border rounded price-input">
                                        </td>
                                        <td class="p-2 border">
                                            <input type="text" class="w-full border rounded item-total bg-gray-50" value="0.00" readonly>
                                        </td>
                                        <td class="p-2 border text-center">
                                            <button type="button" class="remove-item text-red-600">Eliminar</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-600">Subtotal</div>
                            <div class="text-xl font-bold" id="subtotal-display">$0.00</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-600">Descuento</div>
                            <div class="text-xl font-bold" id="discount-display">$0.00</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-600">Total</div>
                            <div class="text-xl font-bold" id="total-display">$0.00</div>
                        </div>
                    </div>

                    <button class="px-4 py-2 bg-black text-white rounded">
                        Guardar cotización
                    </button>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const itemsBody = document.getElementById('items-body');
            const addItemBtn = document.getElementById('add-item');
            const discountInput = document.getElementById('discount');
            let index = 1;

            function recalculateTotals() {
                let subtotal = 0;

                document.querySelectorAll('#items-body tr').forEach(row => {
                    const qty = parseFloat(row.querySelector('.quantity-input')?.value || 0);
                    const price = parseFloat(row.querySelector('.price-input')?.value || 0);
                    const total = qty * price;

                    row.querySelector('.item-total').value = total.toFixed(2);
                    subtotal += total;
                });

                const discount = parseFloat(discountInput.value || 0);
                const grandTotal = Math.max(subtotal - discount, 0);

                document.getElementById('subtotal-display').textContent = '$' + subtotal.toFixed(2);
                document.getElementById('discount-display').textContent = '$' + discount.toFixed(2);
                document.getElementById('total-display').textContent = '$' + grandTotal.toFixed(2);
            }

            function bindRowEvents(row) {
                row.querySelector('.service-select').addEventListener('change', function () {
                    const selected = this.options[this.selectedIndex];
                    const price = selected.getAttribute('data-price');
                    const name = selected.getAttribute('data-name');

                    if (price) {
                        row.querySelector('.price-input').value = parseFloat(price).toFixed(2);
                    }

                    if (name && !row.querySelector('.description-input').value) {
                        row.querySelector('.description-input').value = name;
                    }

                    recalculateTotals();
                });

                row.querySelector('.quantity-input').addEventListener('input', recalculateTotals);
                row.querySelector('.price-input').addEventListener('input', recalculateTotals);
                row.querySelector('.remove-item').addEventListener('click', function () {
                    if (document.querySelectorAll('#items-body tr').length > 1) {
                        row.remove();
                        recalculateTotals();
                    }
                });
            }

            addItemBtn.addEventListener('click', function () {
                const row = document.createElement('tr');

                row.innerHTML = `
                    <td class="p-2 border">
                        <select name="items[${index}][service_id]" class="w-full border rounded service-select">
                            <option value="">Manual</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}" data-price="{{ $service->base_price }}" data-name="{{ $service->name }}">
                                    {{ $service->name }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td class="p-2 border">
                        <input type="text" name="items[${index}][description]" class="w-full border rounded description-input">
                    </td>
                    <td class="p-2 border">
                        <input type="number" name="items[${index}][quantity]" min="1" value="1" class="w-full border rounded quantity-input">
                    </td>
                    <td class="p-2 border">
                        <input type="number" step="0.01" min="0" name="items[${index}][unit_price]" value="0" class="w-full border rounded price-input">
                    </td>
                    <td class="p-2 border">
                        <input type="text" class="w-full border rounded item-total bg-gray-50" value="0.00" readonly>
                    </td>
                    <td class="p-2 border text-center">
                        <button type="button" class="remove-item text-red-600">Eliminar</button>
                    </td>
                `;

                itemsBody.appendChild(row);
                bindRowEvents(row);
                index++;
            });

            document.querySelectorAll('#items-body tr').forEach(bindRowEvents);
            discountInput.addEventListener('input', recalculateTotals);
            recalculateTotals();
        });
    </script>
    @endpush
</x-app-layout>