<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pagos</h2>
            <a href="{{ route('payments.create') }}" class="px-4 py-2 bg-black text-white rounded">
                Nuevo pago
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-6 overflow-x-auto">
                @if(session('success'))
                    <div class="mb-4 text-green-700">{{ session('success') }}</div>
                @endif

                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2">Fecha</th>
                            <th class="py-2">Cliente</th>
                            <th class="py-2">Evento</th>
                            <th class="py-2">Monto</th>
                            <th class="py-2">Método</th>
                            <th class="py-2">Estatus</th>
                            <th class="py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr class="border-b">
                                <td class="py-2">{{ $payment->payment_date->format('d/m/Y') }}</td>
                                <td class="py-2">{{ $payment->client->full_name }}</td>
                                <td class="py-2">{{ $payment->event?->title ?? 'Sin evento' }}</td>
                                <td class="py-2">${{ number_format($payment->amount, 2) }}</td>
                                <td class="py-2">{{ $payment->method }}</td>
                                <td class="py-2">{{ $payment->status }}</td>
                                <td class="py-2 flex gap-2">
                                    <a href="{{ route('payments.show', $payment) }}" class="text-blue-600">Ver</a>
                                    <a href="{{ route('payments.edit', $payment) }}" class="text-yellow-600">Editar</a>
                                    <form action="{{ route('payments.destroy', $payment) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4">No hay pagos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $payments->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>