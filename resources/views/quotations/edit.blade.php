<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Editar cotización</h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
            <div class="rounded bg-white p-6 shadow">
                <form action="{{ route('quotations.update', $quotation) }}" method="POST" id="quotation-form" class="space-y-6">
                    @csrf
                    @method('PUT')
                    @include('quotations._form', ['submitLabel' => 'Actualizar cotización'])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
