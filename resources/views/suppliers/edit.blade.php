<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Editar proveedor</h2>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl rounded bg-white p-6 shadow">
            <form action="{{ route('suppliers.update', $supplier) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')
                @include('suppliers._form', ['submitLabel' => 'Actualizar proveedor'])
            </form>
        </div>
    </div>
</x-app-layout>
