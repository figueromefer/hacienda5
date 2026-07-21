<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar evento</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <form action="{{ route('events.update', $event) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="client_id" class="block mb-1">Cliente</label>
                        <select id="client_id" name="client_id" class="w-full border rounded" required>
                            <option value="">Selecciona</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" @selected((string) old('client_id', $event->client_id) === (string) $client->id)>{{ $client->full_name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                    </div>

                    <div>
                        <label for="title" class="block mb-1">Título</label>
                        <input id="title" type="text" name="title" class="w-full border rounded" value="{{ old('title', $event->title) }}" required>
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <label for="event_type" class="block mb-1">Tipo de evento</label>
                        <input id="event_type" type="text" name="event_type" class="w-full border rounded" value="{{ old('event_type', $event->event_type) }}" required>
                        <x-input-error :messages="$errors->get('event_type')" class="mt-2" />
                    </div>

                    <div>
                        <label for="status" class="block mb-1">Estatus</label>
                        <select id="status" name="status" class="w-full border rounded" required>
                            @foreach(\App\Models\Event::STATUSES as $status)
                                @php $label = (new \App\Models\Event(['status' => $status]))->status_label; @endphp
                                <option value="{{ $status }}" @selected(old('status', $event->status) === $status)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div>
                        <label for="event_date" class="block mb-1">Fecha</label>
                        <input id="event_date" type="date" name="event_date" class="w-full border rounded" value="{{ old('event_date', $event->event_date->format('Y-m-d')) }}" required>
                        <x-input-error :messages="$errors->get('event_date')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><label for="start_time" class="block mb-1">Hora inicio</label><input id="start_time" type="time" name="start_time" class="w-full border rounded" value="{{ old('start_time', $event->start_time) }}"></div>
                        <div><label for="end_time" class="block mb-1">Hora fin</label><input id="end_time" type="time" name="end_time" class="w-full border rounded" value="{{ old('end_time', $event->end_time) }}"></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="guest_count" class="block mb-1">Invitados</label>
                            <input id="guest_count" type="number" min="0" name="guest_count" class="w-full border rounded" value="{{ old('guest_count', $event->guest_count) }}">
                            <x-input-error :messages="$errors->get('guest_count')" class="mt-2" />
                        </div>
                        <div>
                            <label for="budget_estimate" class="block mb-1">Presupuesto estimado total</label>
                            <x-money-input id="budget_estimate" name="budget_estimate" :value="old('budget_estimate', $event->budget_estimate)" />
                            <x-input-error :messages="$errors->get('budget_estimate')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="block mb-1">Notas</label>
                        <textarea id="notes" name="notes" class="w-full border rounded">{{ old('notes', $event->notes) }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>

                    <div class="flex gap-3">
                        <button class="px-4 py-2 bg-black text-white rounded">Actualizar evento</button>
                        <a href="{{ route('events.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
