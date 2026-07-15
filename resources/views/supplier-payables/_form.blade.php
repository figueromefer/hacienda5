@csrf
@if(isset($supplierPayable)) @method('PUT') @endif
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <label>Proveedor *<select name="supplier_id" class="mt-1 w-full rounded border" required><option value="">Selecciona</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string)old('supplier_id', $supplierPayable->supplier_id ?? '') === (string)$supplier->id)>{{ $supplier->name }}{{ $supplier->is_active ? '' : ' (archivado)' }}</option>@endforeach</select></label>
    <label>Concepto<select name="expense_concept_id" class="mt-1 w-full rounded border"><option value="">Sin concepto</option>@foreach($expenseConcepts as $concept)<option value="{{ $concept->id }}" @selected((string)old('expense_concept_id', $supplierPayable->expense_concept_id ?? '') === (string)$concept->id)>{{ $concept->name }}</option>@endforeach</select></label>
    <label>Evento<select name="event_id" class="mt-1 w-full rounded border"><option value="">Sin evento</option>@foreach($events as $event)<option value="{{ $event->id }}" @selected((string)old('event_id', $supplierPayable->event_id ?? '') === (string)$event->id)>{{ $event->title }} · {{ $event->event_date?->format('d/m/Y') }}</option>@endforeach</select></label>
    <label>Vencimiento<input type="date" name="due_date" class="mt-1 w-full rounded border" value="{{ old('due_date', isset($supplierPayable) ? $supplierPayable->due_date?->format('Y-m-d') : '') }}"></label>
    <label class="md:col-span-2">Descripción *<input name="description" required maxlength="255" class="mt-1 w-full rounded border" value="{{ old('description', $supplierPayable->description ?? '') }}"></label>
    <label>Monto total *<input type="number" step="0.01" min="0.01" name="total_amount" required class="mt-1 w-full rounded border" value="{{ old('total_amount', $supplierPayable->total_amount ?? '') }}"></label>
    <label class="md:col-span-2">Notas<textarea name="notes" class="mt-1 w-full rounded border">{{ old('notes', $supplierPayable->notes ?? '') }}</textarea></label>
</div>
@if($errors->any())<div class="mt-4 rounded bg-red-50 p-3 text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="mt-6 flex gap-2"><button class="rounded bg-black px-4 py-3 text-white">Guardar</button><a href="{{ route('supplier-payables.index') }}" class="rounded bg-gray-200 px-4 py-3">Cancelar</a></div>
