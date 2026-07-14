@if ($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
        <div class="font-semibold">Revisa la información del proveedor.</div>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div>
    <label for="name" class="block font-medium text-gray-700">Nombre o razón social</label>
    <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name', $supplier?->name) }}" class="mt-1 w-full rounded border-gray-300">
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label for="contact_name" class="block font-medium text-gray-700">Nombre de contacto</label>
        <input id="contact_name" name="contact_name" type="text" maxlength="255" value="{{ old('contact_name', $supplier?->contact_name) }}" class="mt-1 w-full rounded border-gray-300">
    </div>

    <div>
        <label for="rfc" class="block font-medium text-gray-700">RFC</label>
        <input id="rfc" name="rfc" type="text" maxlength="20" value="{{ old('rfc', $supplier?->rfc) }}" class="mt-1 w-full rounded border-gray-300 uppercase">
    </div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label for="phone" class="block font-medium text-gray-700">Teléfono</label>
        <input id="phone" name="phone" type="tel" maxlength="30" value="{{ old('phone', $supplier?->phone) }}" class="mt-1 w-full rounded border-gray-300">
    </div>

    <div>
        <label for="email" class="block font-medium text-gray-700">Correo electrónico</label>
        <input id="email" name="email" type="email" maxlength="255" value="{{ old('email', $supplier?->email) }}" class="mt-1 w-full rounded border-gray-300">
    </div>
</div>

<div>
    <label for="address" class="block font-medium text-gray-700">Domicilio</label>
    <textarea id="address" name="address" rows="3" class="mt-1 w-full rounded border-gray-300">{{ old('address', $supplier?->address) }}</textarea>
</div>

<div>
    <label for="notes" class="block font-medium text-gray-700">Notas</label>
    <textarea id="notes" name="notes" rows="4" class="mt-1 w-full rounded border-gray-300">{{ old('notes', $supplier?->notes) }}</textarea>
</div>

<div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
    <a href="{{ route('suppliers.index') }}" class="rounded bg-gray-200 px-4 py-3 text-center font-medium text-gray-800">Cancelar</a>
    <button class="rounded bg-black px-4 py-3 font-medium text-white">{{ $submitLabel }}</button>
</div>
