<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Subir documento</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block mb-1">Cliente</label>
                        <select name="client_id" class="w-full border rounded">
                            <option value="">Sin cliente</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1">Evento</label>
                        <select name="event_id" class="w-full border rounded">
                            <option value="">Sin evento</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}">{{ $event->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1">Categoría</label>
                        <select name="category" class="w-full border rounded">
                            <option value="contract">Contrato</option>
                            <option value="receipt">Recibo</option>
                            <option value="identification">Identificación</option>
                            <option value="voucher">Comprobante</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1">Archivo</label>
                        <input type="file" name="file" class="w-full border rounded">
                    </div>

                    <div>
                        <label class="block mb-1">Notas</label>
                        <textarea name="notes" class="w-full border rounded"></textarea>
                    </div>

                    <button class="px-4 py-2 bg-black text-white rounded">
                        Guardar documento
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>