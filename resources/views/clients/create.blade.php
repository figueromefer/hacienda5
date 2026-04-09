<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo cliente</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                @if ($errors->any())
                    <div class="mb-6 rounded border border-red-200 bg-red-50 p-4 text-red-700">
                        <div class="font-semibold mb-2">Corrige los siguientes errores:</div>
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('clients.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1">Tipo</label>
                            <select name="type" class="w-full border rounded">
                                <option value="active" @selected(old('type') === 'active')>Activo</option>
                                <option value="prospect" @selected(old('type') === 'prospect')>Prospecto</option>
                                <option value="past" @selected(old('type') === 'past')>Pasado</option>
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
                            <label class="block mb-1">Email del cliente</label>
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

                        <div class="md:col-span-2">
                            <label class="block mb-1">Origen</label>
                            <input type="text" name="source" class="w-full border rounded" value="{{ old('source') }}">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block mb-1">Notas</label>
                            <textarea name="notes" class="w-full border rounded" rows="4">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="border rounded-lg p-5 bg-gray-50">
                        <div class="flex items-center gap-3">
                            <input
                                id="create_portal_access"
                                type="checkbox"
                                name="create_portal_access"
                                value="1"
                                @checked(old('create_portal_access'))
                                class="rounded border-gray-300"
                            >
                            <label for="create_portal_access" class="font-medium">
                                Crear acceso al portal del cliente
                            </label>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block mb-1">Email de acceso</label>
                                <input type="email" name="portal_email" class="w-full border rounded" value="{{ old('portal_email') }}">
                            </div>

                            <div>
                                <label class="block mb-1">Contraseña inicial</label>
                                <input type="text" name="portal_password" class="w-full border rounded" value="{{ old('portal_password') }}">
                            </div>
                        </div>

                        <p class="mt-3 text-sm text-gray-600">
                            Si activas esta opción se creará un usuario con rol <strong>cliente</strong> y se vinculará automáticamente con este cliente.
                        </p>
                    </div>

                    <button class="px-4 py-2 bg-black text-white rounded">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>