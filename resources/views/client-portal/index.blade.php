<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Portal del cliente
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded p-6">
                <h3 class="text-lg font-semibold mb-4">Mis datos</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><strong>Nombre:</strong> {{ $client->full_name }}</div>
                    <div><strong>Empresa:</strong> {{ $client->company_name ?: '-' }}</div>
                    <div><strong>Email:</strong> {{ $client->email ?: $client->user?->email ?: '-' }}</div>
                    <div><strong>Teléfono:</strong> {{ $client->phone ?: '-' }}</div>
                    <div><strong>Teléfono alterno:</strong> {{ $client->alternate_phone ?: '-' }}</div>
                    <div><strong>Tipo:</strong> {{ $client->type }}</div>
                </div>

                @if($client->notes)
                    <div class="mt-4">
                        <strong>Notas:</strong>
                        <p class="mt-1 text-gray-700">{{ $client->notes }}</p>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow rounded p-6">
                    <h3 class="text-lg font-semibold mb-4">Mis eventos</h3>

                    <div class="space-y-3">
                        @forelse($client->events as $event)
                            <div class="border rounded p-4">
                                <div class="font-semibold">{{ $event->title }}</div>
                                <div class="text-sm text-gray-600 mt-1">
                                    {{ $event->event_type }} · {{ $event->event_date->format('d/m/Y') }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    Estatus: {{ $event->status }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    Horario: {{ $event->start_time ?: '-' }} a {{ $event->end_time ?: '-' }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    Invitados: {{ $event->guest_count ?? '-' }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    Total: ${{ number_format($event->total_amount ?? 0, 2) }}
                                </div>
                                @if($event->notes)
                                    <div class="mt-2 text-sm text-gray-700">
                                        {{ $event->notes }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p>No tienes eventos registrados.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white shadow rounded p-6">
                    <h3 class="text-lg font-semibold mb-4">Mis pagos</h3>

                    <div class="space-y-3">
                        @forelse($client->payments as $payment)
                            <div class="border rounded p-4">
                                <div class="font-semibold">${{ number_format($payment->amount, 2) }}</div>
                                <div class="text-sm text-gray-600 mt-1">
                                    Fecha: {{ $payment->payment_date->format('d/m/Y') }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    Método: {{ $payment->method }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    Estatus: {{ $payment->status }}
                                </div>
                                @if($payment->reference)
                                    <div class="text-sm text-gray-600">
                                        Referencia: {{ $payment->reference }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p>No tienes pagos registrados.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded p-6">
                <h3 class="text-lg font-semibold mb-4">Mis documentos</h3>

                <div class="space-y-3">
                    @forelse($client->documents as $document)
                        <div class="border rounded p-4 flex items-center justify-between gap-4">
                            <div>
                                <div class="font-semibold">{{ $document->original_name }}</div>
                                <div class="text-sm text-gray-600">
                                    Categoría: {{ $document->category }}
                                </div>
                                @if($document->created_at)
                                    <div class="text-sm text-gray-600">
                                        Subido: {{ $document->created_at->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                            </div>

                            <a
                                href="{{ asset('storage/' . $document->file_path) }}"
                                target="_blank"
                                class="px-3 py-2 bg-black text-white rounded text-sm"
                            >
                                Ver documento
                            </a>
                        </div>
                    @empty
                        <p>No tienes documentos disponibles.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>