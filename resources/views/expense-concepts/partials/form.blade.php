<div>
    <label for="name" class="mb-1 block">Nombre <span class="text-red-700">*</span></label>
    <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name', $expenseConcept->name ?? '') }}" class="w-full rounded border-gray-300">
    @error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
</div>
<div>
    <label for="description" class="mb-1 block">Descripción</label>
    <textarea id="description" name="description" rows="4" class="w-full rounded border-gray-300">{{ old('description', $expenseConcept->description ?? '') }}</textarea>
    @error('description')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
</div>
