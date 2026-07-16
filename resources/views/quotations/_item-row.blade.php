<tr>
    <td data-label="Servicio" class="border p-2">
        <select name="items[{{ $index }}][service_id]" class="service-select w-full rounded border-gray-300">
            <option value="">Manual</option>
            @foreach($services as $service)
                <option
                    value="{{ $service->id }}"
                    data-price="{{ $service->base_price }}"
                    data-name="{{ $service->name }}"
                    @selected((string) ($item['service_id'] ?? '') === (string) $service->id)
                >{{ $service->name }}</option>
            @endforeach
        </select>
    </td>
    <td data-label="Descripción" class="border p-2">
        <input type="text" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" class="description-input w-full rounded border-gray-300" required>
        @error("items.$index.description")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </td>
    <td data-label="Cantidad" class="border p-2">
        <input type="number" name="items[{{ $index }}][quantity]" min="1" value="{{ $item['quantity'] ?? 1 }}" inputmode="numeric" class="quantity-input w-full rounded border-gray-300" required>
        @error("items.$index.quantity")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </td>
    <td data-label="Precio unitario" class="border p-2">
        <x-money-input
            name="items[{{ $index }}][unit_price]"
            :value="$item['unit_price'] ?? '0.00'"
            min="0"
            class="price-input"
            x-on:input="$dispatch('quotation-money-change')"
        />
        @error("items.$index.unit_price")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </td>
    <td data-label="Total" class="item-total whitespace-nowrap border p-2 font-medium">$ 0.00</td>
    <td data-label="Acciones" class="border p-2 text-center">
        <button type="button" class="remove-item text-red-700">Eliminar</button>
    </td>
</tr>
