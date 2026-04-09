<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detalle del cliente
            </h2>
            <a href="{{ route('clients.edit', $client) }}" class="px-4 py-2 bg-black text-white rounded">
                Editar cliente
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><strong>Nombre:</strong> {{ $client->full_name }}</div>
                    <div><strong>Tipo:</strong> {{ $client->type }}</div>
                    <div><strong>Empresa:</strong> {{ $client->company_name ?: '-' }}</div>
                    <div><strong>Email:</strong> {{ $client->email ?: '-' }}</div>
                    <div><strong>Teléfono:</strong> {{ $client->phone ?: '-' }}</div>
                    <div><strong>Teléfono alterno:</strong> {{ $client->alternate_phone ?: '-' }}</div>
                    <div><strong>Origen:</strong> {{ $client->source ?: '-' }}</div>
                    <div>
                        <strong>Acceso al portal:</strong>
                        @if($client->user)
                            <span class="text-green-700">Sí</span>
                        @else
                            <span class="text-red-700">No</span>
                        @endif
                    </div>
                </div>

                @if($client->user)
                    <div class="mt-4 rounded border border-green-200 bg-green-50 p-4">
                        <div><strong>Usuario vinculado:</strong> {{ $client->user->name }}</div>
                        <div><strong>Email de acceso:</strong> {{ $client->user->email }}</div>
                    </div>
                @endif

                @if($client->notes)
                    <div class="mt-4">
                        <strong>Notas:</strong>
                        <p class="mt-1 text-gray-700">{{ $client->notes }}</p>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow rounded p-6">
                    <h3 class="text-lg font-semibold mb-4">Eventos</h3>

                    <div class="space-y-3">
                        @forelse($client->events as $event)
                            <div class="border rounded p-3">
                                <div class="font-medium">{{ $event->title }}</div>
                                <div class="text-sm text-gray-600">
                                    {{ $event->event_type }} · {{ $event->event_date->format('d/m/Y') }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    Estatus: {{ $event->status }}
                                </div>
                                <a href="{{ route('events.show', $event) }}" class="text-blue-600 text-sm">
                                    Ver evento
                                </a>
                            </div>
                        @empty
                            <p>No hay eventos registrados.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white shadow rounded p-6">
                    <h3 class="text-lg font-semibold mb-4">Cotizaciones</h3>

                    <div class="space-y-3">
                        @forelse($client->quotations as $quotation)
                            <div class="border rounded p-3">
                                <div class="font-medium">{{ $quotation->folio }}</div>
                                <div class="text-sm text-gray-600">
                                    Estatus: {{ $quotation->status }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    Total: ${{ number_format($quotation->total, 2) }}
                                </div>
                                <a href="{{ route('quotations.show', $quotation) }}" class="text-blue-600 text-sm">
                                    Ver cotización
                                </a>
                            </div>
                        @empty
                            <p>No hay cotizaciones registradas.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white shadow rounded p-6">
                    <h3 class="text-lg font-semibold mb-4">Pagos</h3>

                    <div class="space-y-3">
                        @forelse($client->payments as $payment)
                            <div class="border rounded p-3">
                                <div class="font-medium">${{ number_format($payment->amount, 2) }}</div>
                                <div class="text-sm text-gray-600">
                                    {{ $payment->payment_date->format('d/m/Y') }} · {{ $payment->status }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    Método: {{ $payment->method }}
                                </div>
                                <a href="{{ route('payments.show', $payment) }}" class="text-blue-600 text-sm">
                                    Ver pago
                                </a>
                            </div>
                        @empty
                            <p>No hay pagos registrados.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white shadow rounded p-6">
                    <h3 class="text-lg font-semibold mb-4">Documentos</h3>

                    <div class="space-y-3">
                        @forelse($client->documents as $document)
                            <div class="border rounded p-3">
                                <div class="font-medium">{{ $document->original_name }}</div>
                                <div class="text-sm text-gray-600">
                                    Categoría: {{ $document->category }}
                                </div>
                                <a href="{{ route('documents.show', $document) }}" class="text-blue-600 text-sm">
                                    Ver documento
                                </a>
                            </div>
                        @empty
                            <p>No hay documentos registrados.</p>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>