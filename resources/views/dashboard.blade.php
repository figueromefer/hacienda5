<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 shadow rounded">Clientes: {{ $clientsCount }}</div>
            <div class="bg-white p-4 shadow rounded">Eventos: {{ $eventsCount }}</div>
            <div class="bg-white p-4 shadow rounded">Pagos pendientes: ${{ number_format($pendingPayments, 2) }}</div>
            <div class="bg-white p-4 shadow rounded">Cotizaciones draft: {{ $draftQuotations }}</div>
        </div>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            <div class="bg-white p-6 shadow rounded">
                <h3 class="text-lg font-semibold mb-4">Próximos eventos</h3>
                <div class="space-y-3">
                    @forelse ($nextEvents as $event)
                        <div class="border-b pb-2">
                            <div class="font-medium">{{ $event->title }}</div>
                            <div class="text-sm text-gray-600">
                                {{ $event->client->full_name }} — {{ $event->event_date->format('d/m/Y') }}
                            </div>
                        </div>
                    @empty
                        <p>No hay próximos eventos.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>