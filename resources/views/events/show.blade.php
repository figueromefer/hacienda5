<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $event->title }}
            </h2>
            <a href="{{ route('events.edit', $event) }}" class="px-4 py-2 bg-black text-white rounded">
                Editar evento
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div><strong>Cliente:</strong> {{ $event->client->full_name }}</div>
                    <div><strong>Tipo:</strong> {{ $event->event_type }}</div>
                    <div><strong>Estatus:</strong> {{ $event->status }}</div>
                    <div><strong>Fecha:</strong> {{ $event->event_date->format('d/m/Y') }}</div>
                    <div><strong>Hora inicio:</strong> {{ $event->start_time ?? '-' }}</div>
                    <div><strong>Hora fin:</strong> {{ $event->end_time ?? '-' }}</div>
                    <div><strong>Invitados:</strong> {{ $event->guest_count ?? '-' }}</div>
                    <div><strong>Presupuesto estimado:</strong> ${{ number_format($event->budget_estimate ?? 0, 2) }}</div>
                    <div><strong>Total:</strong> ${{ number_format($event->total_amount, 2) }}</div>
                </div>

                @if($event->notes)
                    <div class="mt-4">
                        <strong>Notas generales:</strong>
                        <p>{{ $event->notes }}</p>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow rounded p-6">
                    <h3 class="text-lg font-semibold mb-4">Pagos</h3>
                    <div class="space-y-3">
                        @forelse($event->payments as $payment)
                            <div class="border rounded p-3">
                                <div><strong>Monto:</strong> ${{ number_format($payment->amount, 2) }}</div>
                                <div><strong>Fecha:</strong> {{ $payment->payment_date->format('d/m/Y') }}</div>
                                <div><strong>Estatus:</strong> {{ $payment->status }}</div>
                                <div><strong>Método:</strong> {{ $payment->method }}</div>
                            </div>
                        @empty
                            <p>No hay pagos registrados.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white shadow rounded p-6">
                    <h3 class="text-lg font-semibold mb-4">Documentos</h3>
                    <div class="space-y-3">
                        @forelse($event->documents as $document)
                            <div class="border rounded p-3">
                                <div><strong>Archivo:</strong> {{ $document->original_name }}</div>
                                <div><strong>Categoría:</strong> {{ $document->category }}</div>
                                <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="text-blue-600">
                                    Ver documento
                                </a>
                            </div>
                        @empty
                            <p>No hay documentos cargados.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white shadow rounded p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Tareas</h3>
                    </div>

                    <form action="{{ route('events.tasks.store', $event) }}" method="POST" class="space-y-4 mb-6 border rounded p-4 bg-gray-50">
                        @csrf

                        <div>
                            <label class="block mb-1">Título</label>
                            <input type="text" name="title" class="w-full border rounded" required>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block mb-1">Fecha límite</label>
                                <input type="datetime-local" name="due_date" class="w-full border rounded">
                            </div>

                            <div>
                                <label class="block mb-1">Estatus</label>
                                <select name="status" class="w-full border rounded">
                                    <option value="pending">Pendiente</option>
                                    <option value="done">Hecha</option>
                                    <option value="cancelled">Cancelada</option>
                                </select>
                            </div>

                            <div>
                                <label class="block mb-1">Responsable</label>
                                <select name="assigned_to" class="w-full border rounded">
                                    <option value="">Sin asignar</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1">Notas</label>
                            <textarea name="notes" class="w-full border rounded"></textarea>
                        </div>

                        <button class="px-4 py-2 bg-black text-white rounded">
                            Agregar tarea
                        </button>
                    </form>

                    <div class="space-y-3">
                        @forelse($event->tasks as $task)
                            <div class="border rounded p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="font-semibold">{{ $task->title }}</div>
                                        <div class="text-sm text-gray-600 mt-1">
                                            Estatus: {{ $task->status }}
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            Responsable: {{ $task->assignedUser?->name ?? 'Sin asignar' }}
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            Fecha límite: {{ $task->due_date?->format('d/m/Y H:i') ?? '-' }}
                                        </div>

                                        @if($task->notes)
                                            <div class="mt-2 text-sm text-gray-700">
                                                {{ $task->notes }}
                                            </div>
                                        @endif
                                    </div>

                                    <form action="{{ route('events.tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('¿Eliminar esta tarea?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p>No hay tareas registradas.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white shadow rounded p-6">
                    <h3 class="text-lg font-semibold mb-4">Notas del evento</h3>

                    <form action="{{ route('events.notes.store', $event) }}" method="POST" class="space-y-4 mb-6 border rounded p-4 bg-gray-50">
                        @csrf

                        <div>
                            <label class="block mb-1">Nueva nota</label>
                            <textarea name="note" class="w-full border rounded" rows="4" required></textarea>
                        </div>

                        <button class="px-4 py-2 bg-black text-white rounded">
                            Agregar nota
                        </button>
                    </form>

                    <div class="space-y-3">
                        @forelse($event->notesList->sortByDesc('created_at') as $note)
                            <div class="border rounded p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-sm text-gray-500">
                                            {{ $note->user->name }} · {{ $note->created_at->format('d/m/Y H:i') }}
                                        </div>
                                        <div class="mt-2 text-gray-800">
                                            {{ $note->note }}
                                        </div>
                                    </div>

                                    <form action="{{ route('events.notes.destroy', $note) }}" method="POST" onsubmit="return confirm('¿Eliminar esta nota?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p>No hay notas registradas.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded p-6">
                <h3 class="text-lg font-semibold mb-4">Cotizaciones relacionadas</h3>
                <div class="space-y-3">
                    @forelse($event->quotations as $quotation)
                        <div class="border rounded p-3 flex items-center justify-between">
                            <div>
                                <div><strong>{{ $quotation->folio }}</strong></div>
                                <div>Estatus: {{ $quotation->status }}</div>
                                <div>Total: ${{ number_format($quotation->total, 2) }}</div>
                            </div>
                            <a href="{{ route('quotations.show', $quotation) }}" class="text-blue-600">Ver cotización</a>
                        </div>
                    @empty
                        <p>No hay cotizaciones relacionadas.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>