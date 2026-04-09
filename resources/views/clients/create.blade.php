<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo cliente</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <form action="{{ route('clients.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block mb-1">Tipo</label>
                        <select name="type" class="w-full border rounded">
                            <option value="active">Activo</option>
                            <option value="prospect">Prospecto</option>
                            <option value="past">Pasado</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1">Nombre completo</label>
                        <input type="text" name="full_name" class="w-full border rounded" value="{{ old('full_name') }}">
                    </div>

                    <div>
                        <label class="block mb-1">Empresa</label>
                        <input type="text" name="company_name" class="w-full border rounded" value="{{ old('company_name') }}">
                    </div>

                    <div>
                        <label class="block mb-1">Email</label>
                        <input type="email" name="email" class="w-full border rounded" value="{{ old('email') }}">
                    </div>

                    <div>
                        <label class="block mb-1">Teléfono</label>
                        <input type="text" name="phone" class="w-full border rounded" value="{{ old('phone') }}">
                    </div>

                    <div>
                        <label class="block mb-1">Teléfono alterno</label>
                        <input type="text" name="alternate_phone" class="w-full border rounded" value="{{ old('alternate_phone') }}">
                    </div>

                    <div>
                        <label class="block mb-1">Origen</label>
                        <input type="text" name="source" class="w-full border rounded" value="{{ old('source') }}">
                    </div>

                    <div>
                        <label class="block mb-1">Notas</label>
                        <textarea name="notes" class="w-full border rounded">{{ old('notes') }}</textarea>
                    </div>

                    <button class="px-4 py-2 bg-black text-white rounded">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>