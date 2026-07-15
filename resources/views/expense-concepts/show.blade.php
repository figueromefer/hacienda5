<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Detalle del concepto de gasto</h2>
            <a href="{{ route('expense-concepts.edit', $expenseConcept) }}" class="rounded bg-black px-4 py-2 text-center text-white">Editar</a>
        </div>
    </x-slot>
    <div class="py-6">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="space-y-4 rounded bg-white p-4 shadow sm:p-6">
                <div><strong>Nombre:</strong> {{ $expenseConcept->name }}</div>
                <div><strong>Descripción:</strong> {{ $expenseConcept->description ?: 'Sin descripción' }}</div>
                <div><strong>Estado:</strong> {{ $expenseConcept->is_active ? 'Activo' : 'Archivado' }}</div>
                <a href="{{ $expenseConcept->is_active ? route('expense-concepts.index') : route('expense-concepts.archived') }}" class="inline-block text-blue-700 underline">Volver</a>
            </div>
        </div>
    </div>
</x-app-layout>
