<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>

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
                <div class="text-sm text-gray-500">Pendiente</div>
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
