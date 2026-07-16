<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar cliente</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <form action="{{ route('clients.update', $client) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="type" class="block mb-1">Tipo</label>
                            <select id="type" name="type" class="w-full border rounded" required>
                                <option value="prospect" @selected(old('type', $client->type) === 'prospect')>Prospecto</option>
                                <option value="active" @selected(old('type', $client->type) === 'active')>Activo</option>
                                <option value="past" @selected(old('type', $client->type) === 'past')>Anterior</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>

                        <div>
                            <label for="full_name" class="block mb-1">Nombre completo</label>
                            <input id="full_name" type="text" name="full_name" class="w-full border rounded" value="{{ old('full_name', $client->full_name) }}" required>
                            <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                        </div>

                        <div>
                            <label for="company_name" class="block mb-1">Empresa</label>
                            <input id="company_name" type="text" name="company_name" class="w-full border rounded" value="{{ old('company_name', $client->company_name) }}">
                            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                        </div>

                        <div>
                            <label for="email" class="block mb-1">Correo y acceso al portal</label>
                            <input id="email" type="email" name="email" class="w-full border rounded" value="{{ old('email', $client->email) }}" required autocomplete="username">
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <label for="phone" class="block mb-1">Teléfono</label>
                            <input id="phone" type="text" name="phone" class="w-full border rounded" value="{{ old('phone', $client->phone) }}">
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <div>
                            <label for="alternate_phone" class="block mb-1">Teléfono alterno</label>
                            <input id="alternate_phone" type="text" name="alternate_phone" class="w-full border rounded" value="{{ old('alternate_phone', $client->alternate_phone) }}">
                            <x-input-error :messages="$errors->get('alternate_phone')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <label for="source" class="block mb-1">Origen</label>
                            <input id="source" type="text" name="source" class="w-full border rounded" value="{{ old('source', $client->source) }}">
                            <x-input-error :messages="$errors->get('source')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <label for="notes" class="block mb-1">Notas</label>
                            <textarea id="notes" name="notes" class="w-full border rounded" rows="4">{{ old('notes', $client->notes) }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>

                    <fieldset class="rounded-lg border bg-gray-50 p-5">
                        <legend class="px-2 font-semibold">Acceso al portal</legend>
                        <p class="mb-4 text-sm text-gray-600">{{ $client->user ? 'Deja la contraseña vacía para conservar la actual.' : 'Este cliente histórico aún no tiene usuario. Define una contraseña para crear su acceso de forma segura.' }}</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="portal_password" class="block mb-1">{{ $client->user ? 'Nueva contraseña (opcional)' : 'Contraseña inicial' }}</label>
                                <input id="portal_password" type="password" name="portal_password" class="w-full border rounded" @required(! $client->user) autocomplete="new-password">
                                <x-input-error :messages="$errors->get('portal_password')" class="mt-2" />
                            </div>
                            <div>
                                <label for="portal_password_confirmation" class="block mb-1">Confirmar nueva contraseña</label>
                                <input id="portal_password_confirmation" type="password" name="portal_password_confirmation" class="w-full border rounded" @required(! $client->user) autocomplete="new-password">
                                <x-input-error :messages="$errors->get('portal_password_confirmation')" class="mt-2" />
                            </div>
                        </div>
                    </fieldset>

                    <button class="px-4 py-2 bg-black text-white rounded">Actualizar cliente</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
