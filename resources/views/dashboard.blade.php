<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-6 space-y-6">

        <!-- RESUMEN FINANCIERO -->
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-5 shadow rounded">
                <div class="text-sm text-gray-500">Ingresos</div>
                <div class="text-2xl font-bold text-green-700">${{ number_format($income, 2) }}</div>
            </div>
            <div class="bg-white p-5 shadow rounded">
                <div class="text-sm text-gray-500">Gastos</div>
                <div class="text-2xl font-bold text-red-700">${{ number_format($expenses, 2) }}</div>
            </div>
            <div class="bg-white p-5 shadow rounded">
                <div class="text-sm text-gray-500">Pendiente</div>
                <div class="text-2xl font-bold text-yellow-600">${{ number_format($pendingIncome, 2) }}</div>
            </div>
            <div class="bg-white p-5 shadow rounded">
                <div class="text-sm text-gray-500">Balance</div>
                <div class="text-2xl font-bold {{ $balance >= 0 ? 'text-green-700' : 'text-red-700' }}">${{ number_format($balance, 2) }}</div>
            </div>
        </div>

        <!-- INFO GENERAL -->
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 shadow rounded">Clientes: {{ $clientsCount }}</div>
            <div class="bg-white p-4 shadow rounded">Eventos: {{ $eventsCount }}</div>
            <div class="bg-white p-4 shadow rounded">Cotizaciones draft: {{ $draftQuotations }}</div>
        </div>

        <!-- EVENTOS -->
        <div class="max-w-7xl mx-auto">
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