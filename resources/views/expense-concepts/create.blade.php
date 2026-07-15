<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold leading-tight text-gray-800">Nuevo concepto de gasto</h2></x-slot>
    <div class="py-6">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded bg-white p-4 shadow sm:p-6">
                <form action="{{ route('expense-concepts.store') }}" method="POST" class="space-y-4">
                    @csrf
                    @include('expense-concepts.partials.form')
                    <div class="flex flex-wrap gap-2">
                        <button class="rounded bg-black px-4 py-2 text-white">Guardar concepto</button>
                        <a href="{{ route('expense-concepts.index') }}" class="rounded border px-4 py-2">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
