<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cotizaciones</h2>
            <a href="{{ route('quotations.create') }}" class="px-4 py-2 bg-black text-white rounded">
                Nueva cotización
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6">
                @if(session('success'))
                    <div class="mb-4 text-green-700">{{ session('success') }}</div>
                @endif

                <form method="GET" action="{{ route('quotations.index') }}" class="mb-6 flex flex-col gap-3 sm:flex-row">
                    @if(request('event_id'))
                        <input type="hidden" name="event_id" value="{{ request('event_id') }}">
                    @endif
                    <div class="flex-1">
                        <label for="search" class="sr-only">Buscar cotizaciones</label>
                        <input
                            type="search"
                            name="search"
                            id="search"
                            value="{{ request('search') }}"
                            placeholder="Buscar por folio, cliente, evento o estado"
                            class="w-full rounded border-gray-300"
                        >
                    </div>
                    <button class="rounded bg-gray-800 px-4 py-2 text-white">Buscar</button>
                    @if(request()->filled('search'))
                        <a href="{{ route('quotations.index', request('event_id') ? ['event_id' => request('event_id')] : []) }}" class="rounded bg-gray-200 px-4 py-2 text-center">Limpiar</a>
                    @endif
                </form>

                <table class="responsive-table w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Folio</th>
                            <th class="py-2">Cliente</th>
                            <th class="py-2">Evento</th>
                            <th class="py-2">Estatus</th>
                            <th class="py-2">Total</th>
                            <th class="py-2">Válida hasta</th>
                            <th class="py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($quotations as $quotation)
                            <tr class="border-b">
                                <td data-label="Folio" class="py-2">{{ $quotation->folio }}</td>
                                <td data-label="Cliente" class="py-2">{{ $quotation->client->full_name }}</td>
                                <td data-label="Evento" class="py-2">{{ $quotation->event?->title ?? 'Sin evento' }}</td>
                                <td data-label="Estatus" class="py-2"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $quotation->status_classes }}">{{ $quotation->status_label }}</span></td>
                                <td data-label="Total" class="py-2">$ {{ number_format($quotation->total, 2) }}</td>
                                <td data-label="Válida hasta" class="py-2">{{ $quotation->valid_until?->format('d/m/Y') ?? '-' }}</td>
                                <td data-label="Acciones" class="py-2">
                                    <form method="POST" action="{{ route('quotations.status.update', $quotation) }}" class="mb-2 flex items-center gap-2" onsubmit="return confirm('Cambiar el estado puede afectar los cálculos financieros. ¿Continuar?')">
                                        @csrf @method('PATCH')
                                        <label for="status-{{ $quotation->id }}" class="sr-only">Cambiar estatus</label>
                                        <select id="status-{{ $quotation->id }}" name="status" class="rounded border-gray-300 py-1 text-xs">
                                            @foreach(\App\Support\DomainLabels::QUOTATION_STATUSES as $value => $label)
                                                <option value="{{ $value }}" @selected($quotation->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button class="rounded bg-brand-green px-2 py-1 text-xs font-semibold text-white">Cambiar</button>
                                    </form>
                                    <x-action-buttons
                                        :show="route('quotations.show', $quotation)"
                                        :download="route('quotations.pdf', $quotation)"
                                        :edit="route('quotations.edit', $quotation)"
                                        :delete="route('quotations.destroy', $quotation)"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4">No hay cotizaciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $quotations->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
