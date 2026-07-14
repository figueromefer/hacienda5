<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Proveedores</h2>
                <p class="mt-1 text-sm text-gray-500">Catálogo de proveedores de Hacienda Cinco.</p>
            </div>
            <a href="{{ route('suppliers.create') }}" class="rounded bg-black px-4 py-3 text-center font-medium text-white">Nuevo proveedor</a>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-4">
            <form method="GET" action="{{ route('suppliers.index') }}" class="grid grid-cols-1 gap-3 rounded bg-white p-4 shadow sm:grid-cols-[minmax(0,1fr)_auto_auto]">
                <div>
                    <label for="q" class="sr-only">Buscar proveedor</label>
                    <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Nombre, contacto, teléfono, correo o RFC" class="w-full rounded border-gray-300">
                </div>
                <select name="status" aria-label="Estado de proveedores" class="w-full rounded border-gray-300 sm:w-auto">
                    <option value="" @selected($status !== 'archived')>Activos</option>
                    <option value="archived" @selected($status === 'archived')>Archivados</option>
                </select>
                <button class="rounded bg-[#243834] px-4 py-3 font-medium text-white">Buscar</button>
            </form>

            <div class="rounded bg-white p-4 shadow sm:p-6">
                <table class="responsive-table w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Proveedor</th>
                            <th class="py-2">Contacto</th>
                            <th class="py-2">Teléfono</th>
                            <th class="py-2">Correo</th>
                            <th class="py-2">RFC</th>
                            <th class="py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suppliers as $supplier)
                            <tr class="border-b">
                                <td data-label="Proveedor" class="py-2 font-medium">{{ $supplier->name }}</td>
                                <td data-label="Contacto" class="py-2">{{ $supplier->contact_name ?: '-' }}</td>
                                <td data-label="Teléfono" class="py-2">{{ $supplier->phone ?: '-' }}</td>
                                <td data-label="Correo" class="py-2 break-all">{{ $supplier->email ?: '-' }}</td>
                                <td data-label="RFC" class="py-2">{{ $supplier->rfc ?: '-' }}</td>
                                <td data-label="Acciones" class="py-2">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('suppliers.show', $supplier) }}" class="rounded bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700">Ver</a>
                                        <a href="{{ route('suppliers.edit', $supplier) }}" class="rounded bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700">Editar</a>
                                        @if ($supplier->is_active)
                                            <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" onsubmit="return confirm('¿Archivar este proveedor?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded bg-red-50 px-3 py-2 text-sm font-medium text-red-700">Archivar</button>
                                            </form>
                                        @else
                                            <form action="{{ route('suppliers.restore', $supplier) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button class="rounded bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">Restaurar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-gray-500">
                                    {{ $status === 'archived' ? 'No hay proveedores archivados.' : 'No hay proveedores activos con esos criterios.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">{{ $suppliers->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
