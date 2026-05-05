<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo movimiento</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <form action="{{ route('transactions.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label>Tipo</label>
                            <select name="type" class="w-full border rounded">
                                <option value="income">Ingreso</option>
                                <option value="expense">Gasto</option>
                            </select>
                        </div>
                        <div>
                            <label>Alcance</label>
                            <select name="scope" class="w-full border rounded">
                                <option value="event">Evento</option>
                                <option value="operation">Operación</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label>Cliente</label>
                        <select name="client_id" class="w-full border rounded">
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label>Evento</label>
                        <select name="event_id" class="w-full border rounded">
                            <option value="">Sin evento</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}">{{ $event->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label>Fecha</label>
                            <input type="date" name="transaction_date" class="w-full border rounded">
                        </div>
                        <div>
                            <label>Monto</label>
                            <input type="number" step="0.01" name="amount" class="w-full border rounded">
                        </div>
                    </div>

                    <div>
                        <label>Categoría</label>
                        <input type="text" name="category" class="w-full border rounded">
                    </div>

                    <div>
                        <label>Notas</label>
                        <textarea name="notes" class="w-full border rounded"></textarea>
                    </div>

                    <button class="px-4 py-2 bg-black text-white rounded">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
