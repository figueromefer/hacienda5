<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Nuevo proveedor</h2>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl rounded bg-white p-6 shadow">
            <form action="{{ route('suppliers.store') }}" method="POST" class="space-y-5">
                @csrf
                @include('suppliers._form', ['supplier' => null, 'submitLabel' => 'Guardar proveedor'])
            </form>
        </div>
    </div>
</x-app-layout>
