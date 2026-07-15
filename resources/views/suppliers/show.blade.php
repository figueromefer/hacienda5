<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $supplier->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">Detalle del proveedor</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('suppliers.index') }}" class="rounded bg-gray-200 px-4 py-3 font-medium text-gray-800">Volver</a>
                <a href="{{ route('suppliers.edit', $supplier) }}" class="rounded bg-black px-4 py-3 font-medium text-white">Editar</a>
            </div>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl rounded bg-white p-6 shadow">
            <dl class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div><dt class="text-sm font-medium text-gray-500">Estado</dt><dd class="mt-1">{{ $supplier->is_active ? 'Activo' : 'Archivado' }}</dd></div>
                <div><dt class="text-sm font-medium text-gray-500">Contacto</dt><dd class="mt-1">{{ $supplier->contact_name ?: '-' }}</dd></div>
                <div><dt class="text-sm font-medium text-gray-500">Teléfono</dt><dd class="mt-1">{{ $supplier->phone ?: '-' }}</dd></div>
                <div><dt class="text-sm font-medium text-gray-500">Correo</dt><dd class="mt-1 break-all">{{ $supplier->email ?: '-' }}</dd></div>
                <div><dt class="text-sm font-medium text-gray-500">RFC</dt><dd class="mt-1">{{ $supplier->rfc ?: '-' }}</dd></div>
                <div class="md:col-span-2"><dt class="text-sm font-medium text-gray-500">Domicilio</dt><dd class="mt-1 whitespace-pre-line">{{ $supplier->address ?: '-' }}</dd></div>
                <div class="md:col-span-2"><dt class="text-sm font-medium text-gray-500">Notas</dt><dd class="mt-1 whitespace-pre-line">{{ $supplier->notes ?: '-' }}</dd></div>
            </dl>
        </div>
        <div class="mx-auto mt-5 max-w-4xl rounded bg-white p-6 shadow">
            <div class="flex flex-wrap items-center justify-between gap-3"><h3 class="font-semibold">Cuentas por pagar</h3><a class="text-blue-700" href="{{ route('supplier-payables.index', ['supplier_id' => $supplier->id]) }}">Ver todas</a></div>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3"><div>Pendientes<br><strong>{{ $payableSummary['pending'] }}</strong></div><div>Pago parcial<br><strong>{{ $payableSummary['partially_paid'] }}</strong></div><div>Total pendiente<br><strong>${{ number_format($payableSummary['balance'], 2) }}</strong></div></div>
        </div>
    </div>
</x-app-layout>
