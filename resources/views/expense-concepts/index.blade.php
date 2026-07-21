<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Conceptos de gasto</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('expense-concepts.archived') }}" class="rounded border border-gray-300 px-4 py-2 text-sm">Ver archivados</a>
                <a href="{{ route('expense-concepts.create') }}" class="rounded bg-black px-4 py-2 text-sm text-white">Nuevo concepto</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded bg-white p-4 shadow sm:p-6">
                <form method="GET" action="{{ route('expense-concepts.index') }}" class="mb-6 flex flex-col gap-2 sm:flex-row">
                    <label for="search" class="sr-only">Buscar por nombre o descripción</label>
                    <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Buscar por nombre o descripción" class="w-full rounded border-gray-300 sm:max-w-md">
                    <button class="rounded bg-brand-green px-4 py-2 text-white">Buscar</button>
                    @if($search !== '')
                        <a href="{{ route('expense-concepts.index') }}" class="rounded border px-4 py-2 text-center">Limpiar</a>
                    @endif
                </form>

                <table class="responsive-table w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Nombre</th>
                            <th class="py-2">Descripción</th>
                            <th class="py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenseConcepts as $expenseConcept)
                            <tr class="border-b">
                                <td data-label="Nombre" class="py-2">{{ $expenseConcept->name }}</td>
                                <td data-label="Descripción" class="py-2">{{ $expenseConcept->description ?: '—' }}</td>
                                <td data-label="Acciones" class="py-2">
                                    <x-action-buttons
                                        :show="route('expense-concepts.show', $expenseConcept)"
                                        :edit="route('expense-concepts.edit', $expenseConcept)"
                                        :delete="route('expense-concepts.destroy', $expenseConcept)"
                                        confirm="Este concepto se archivará y dejará de estar disponible para nuevos movimientos."
                                        confirmation-word="ARCHIVAR"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-gray-500">No hay conceptos de gasto activos que mostrar.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">{{ $expenseConcepts->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
