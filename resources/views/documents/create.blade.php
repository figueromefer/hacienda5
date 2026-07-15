<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Subir documento</h2></x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    @if($selectedEvent)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 rounded-lg border bg-gray-50 p-4">
                            <div><span class="block text-sm text-gray-500">Cliente</span><strong>{{ $selectedEvent->client->full_name }}</strong></div>
                            <div><span class="block text-sm text-gray-500">Evento</span><strong>{{ $selectedEvent->title }}</strong></div>
                        </div>
                        <input type="hidden" name="client_id" value="{{ $selectedEvent->client_id }}">
                        <input type="hidden" name="event_id" value="{{ $selectedEvent->id }}">
                    @else
                        <div>
                            <label for="client_id" class="block mb-1">Cliente</label>
                            <select id="client_id" name="client_id" class="w-full border rounded">
                                <option value="">Se derivará del evento</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->full_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                        </div>
                        <div>
                            <label for="event_id" class="block mb-1">Evento</label>
                            <select id="event_id" name="event_id" class="w-full border rounded" required>
                                <option value="">Selecciona un evento</option>
                                @foreach($events as $event)
                                    <option value="{{ $event->id }}" @selected((string) old('event_id') === (string) $event->id)>{{ $event->title }} · {{ $event->client->full_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('event_id')" class="mt-2" />
                        </div>
                    @endif

                    <div>
                        <label for="category" class="block mb-1">Categoría</label>
                        <select id="category" name="category" class="w-full border rounded" required>
                            @foreach(['contract' => 'Contrato', 'receipt' => 'Recibo', 'identification' => 'Identificación', 'voucher' => 'Comprobante', 'other' => 'Otro'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="file" class="block mb-1">Archivo</label>
                        <input id="file" type="file" name="file" class="w-full border rounded" required>
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>
                    <div><label for="notes" class="block mb-1">Notas</label><textarea id="notes" name="notes" class="w-full border rounded">{{ old('notes') }}</textarea></div>

                    <div class="flex gap-3">
                        <button class="px-4 py-2 bg-black text-white rounded">Guardar documento</button>
                        <a href="{{ $selectedEvent ? route('events.show', $selectedEvent) : route('events.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
