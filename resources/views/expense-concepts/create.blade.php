<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold leading-tight text-gray-800">Nuevo concepto de gasto</h2></x-slot>
    <div class="py-6">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded bg-white p-4 shadow sm:p-6">
                <form action="{{ route('expense-concepts.store') }}" method="POST" class="space-y-4">
                    @csrf
                    @include('expense-concepts.partials.form')
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <a href="{{ route('expense-concepts.index') }}" class="inline-flex min-h-11 items-center justify-center rounded border px-4 py-2">Cancelar</a>
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded bg-black px-4 py-2 text-white">Guardar concepto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
