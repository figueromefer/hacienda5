<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @can("manage clients")<a href="{{ route("clients.create") }}" class="rounded bg-black px-3 py-2 text-sm text-white">Nuevo cliente</a>@endcan
                    @can("manage events")<a href="{{ route("events.create") }}" class="rounded bg-black px-3 py-2 text-sm text-white">Nuevo evento</a>@endcan
                    @can("manage quotations")<a href="{{ route("quotations.create") }}" class="rounded bg-black px-3 py-2 text-sm text-white">Nueva cotización</a>@endcan
                    @can("manage payments")<a href="{{ route("transactions.create") }}" class="rounded bg-black px-3 py-2 text-sm text-white">Nuevo movimiento</a>@endcan
                </div>
            </div>

            <form method="GET">
                <select name="period" onchange="this.form.submit()" class="border rounded px-3 py-2">
                    <option value="6" {{ $period == '6' ? 'selected' : '' }}>Últimos 6 meses</option>
                    <option value="12" {{ $period == '12' ? 'selected' : '' }}>Últimos 12 meses</option>
                    <option value="year" {{ $period == 'year' ? 'selected' : '' }}>Este año</option>
                </select>
            </form>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">

        <div class="max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 shadow rounded">
                <div class="text-sm text-gray-500">Ingresos</div>
                <div class="text-2xl font-bold text-green-700">${{ number_format($income, 2) }}</div>
            </div>
            <div class="bg-white p-5 shadow rounded">
                <div class="text-sm text-gray-500">Gastos</div>
                <div class="text-2xl font-bold text-red-700">${{ number_format($expenses, 2) }}</div>
            </div>
            <div class="bg-white p-5 shadow rounded">
                <div class="text-sm text-gray-500">Pendiente por cobrar</div>
                <div class="text-2xl font-bold text-yellow-600">${{ number_format($pendingIncome, 2) }}</div>
            </div>
            <div class="bg-white p-5 shadow rounded">
                <div class="text-sm text-gray-500">Balance</div>
                <div class="text-2xl font-bold {{ $balance >= 0 ? 'text-green-700' : 'text-red-700' }}">${{ number_format($balance, 2) }}</div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto bg-white p-4 sm:p-6 shadow rounded overflow-x-auto">
            <h3 class="text-lg font-semibold mb-4">Flujo financiero</h3>
            <div class="min-w-[36rem] sm:min-w-0">
                <canvas id="financeChart" height="120"></canvas>
            </div>
        </div>

        <section class="mx-auto max-w-7xl rounded-xl bg-white p-4 shadow sm:p-6" aria-labelledby="assigned-tasks-title">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h3 id="assigned-tasks-title" class="text-lg font-semibold">Mis tareas pendientes</h3>
                    <p class="text-sm text-gray-500">Ordenadas por la fecha límite más próxima.</p>
                </div>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">{{ $assignedTasks->count() }}</span>
            </div>

            <div class="space-y-3">
                @forelse($assignedTasks as $task)
                    @php
                        $isOverdue = $task->due_date?->isPast();
                        $isSoon = ! $isOverdue && $task->due_date?->lessThanOrEqualTo(now()->addDays(2));
                        $dateClasses = $isOverdue ? 'border-red-200 bg-red-50 text-red-800' : ($isSoon ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-gray-200 bg-gray-50 text-gray-700');
                    @endphp
                    <article class="rounded-xl border p-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <div class="font-semibold text-gray-900">{{ $task->title }}</div>
                                <div class="mt-1 text-sm text-gray-600">{{ $task->event->title }}</div>
                                <div class="mt-3 inline-flex rounded-lg border px-3 py-2 text-sm font-bold {{ $dateClasses }}">
                                    @if($isOverdue) Vencida · @elseif($isSoon) Próxima · @endif
                                    {{ $task->due_date?->format('d/m/Y H:i') ?? 'Sin fecha límite' }}
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('event-tasks.edit', ['eventTask' => $task, 'origin' => 'dashboard']) }}" class="rounded bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700">Editar</a>
                                <form action="{{ route('event-tasks.complete', $task) }}" method="POST">@csrf @method('PATCH')<button class="rounded bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">Completar</button></form>
                                <form action="{{ route('event-tasks.cancel', $task) }}" method="POST" onsubmit="return confirm('¿Cancelar esta tarea? Se conservará en el historial.')">@csrf @method('PATCH')<button class="rounded bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700">Cancelar</button></form>
                                @can('manage events')
                                    <a href="{{ route('events.show', $task->event_id) }}" class="rounded bg-black px-3 py-2 text-sm font-semibold text-white">Ir al evento</a>
                                @endcan
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed p-6 text-center text-gray-500">No tienes tareas pendientes asignadas.</div>
                @endforelse
            </div>
        </section>

    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('financeChart');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Ingresos',
                        data: @json($chartIncome),
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22,163,74,0.1)',
                        tension: 0.3
                    },
                    {
                        label: 'Gastos',
                        data: @json($chartExpenses),
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220,38,38,0.1)',
                        tension: 0.3
                    },
                    {
                        label: 'Balance',
                        data: @json($chartBalance),
                        borderColor: '#000000',
                        borderDash: [5,5],
                        tension: 0.3
                    }
                ]
            }
        });
    </script>
    @endpush
</x-app-layout>
