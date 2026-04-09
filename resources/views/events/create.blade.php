<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo evento</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <form action="{{ route('events.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block mb-1">Cliente</label>
                        <select name="client_id" class="w-full border rounded">
                            <option value="">Selecciona</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1">Título</label>
                        <input type="text" name="title" class="w-full border rounded" value="{{ old('title') }}">
                    </div>

                    <div>
                        <label class="block mb-1">Tipo de evento</label>
                        <input type="text" name="event_type" class="w-full border rounded" value="{{ old('event_type') }}">
                    </div>

                    <div>
                        <label class="block mb-1">Estatus</label>
                        <select name="status" class="w-full border rounded">
                            <option value="tentative">Tentativo</option>
                            <option value="confirmed">Confirmado</option>
                            <option value="completed">Completado</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1">Fecha</label>
                        <input type="date" name="event_date" class="w-full border rounded" value="{{ old('event_date') }}">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1">Hora inicio</label>
                            <input type="time" name="start_time" class="w-full border rounded" value="{{ old('start_time') }}">
                        </div>
                        <div>
                            <label class="block mb-1">Hora fin</label>
                            <input type="time" name="end_time" class="w-full border rounded" value="{{ old('end_time') }}">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1">Invitados</label>
                            <input type="number" name="guest_count" class="w-full border rounded" value="{{ old('guest_count') }}">
                        </div>
                        <div>
                            <label class="block mb-1">Presupuesto estimado</label>
                            <input type="number" step="0.01" name="budget_estimate" class="w-full border rounded" value="{{ old('budget_estimate') }}">
                        </div>
                    </div>

                    <div>
                        <label class="block mb-1">Monto total</label>
                        <input type="number" step="0.01" name="total_amount" class="w-full border rounded" value="{{ old('total_amount', 0) }}">
                    </div>

                    <div>
                        <label class="block mb-1">Dirección</label>
                        <input type="text" name="address" class="w-full border rounded" value="{{ old('address') }}">
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