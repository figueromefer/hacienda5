<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo servicio</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <form action="{{ route('services.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block mb-1">Nombre</label>
                        <input type="text" name="name" class="w-full border rounded" value="{{ old('name') }}">
                    </div>

                    <div>
                        <label class="block mb-1">Descripción</label>
                        <textarea name="description" class="w-full border rounded">{{ old('description') }}</textarea>
                    </div>

                    <div>
                        <label class="block mb-1">Precio base</label>
                        <input type="number" step="0.01" name="base_price" class="w-full border rounded" value="{{ old('base_price', 0) }}">
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span>Activo</span>
                        </label>
                    </div>

                    <button class="px-4 py-2 bg-black text-white rounded">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>