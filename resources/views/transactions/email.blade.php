<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $transaction->receiptEmailLogs->isEmpty() ? 'Enviar recibo' : 'Reenviar recibo' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">{{ $transaction->reference }} · {{ $transaction->client?->full_name }}</p>
        </div>
    </x-slot>

    <div class="py-6 px-4">
        <div class="max-w-2xl mx-auto bg-white shadow rounded-2xl p-6">
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
                    <div class="font-semibold">Revisa los destinatarios.</div>
                    <ul class="mt-2 list-disc pl-5 text-sm">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('transactions.email.store', $transaction) }}" class="space-y-5">
                @csrf

                <div>
                    <label for="receipt_to" class="block font-medium">Para</label>
                    <textarea id="receipt_to" name="receipt_to" rows="3" class="mt-1 w-full border rounded" placeholder="cliente@gmail.com, familiar@gmail.com">{{ $toRecipients }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Puedes escribir varios correos separados por coma, punto y coma o salto de línea.</p>
                </div>

                <div>
                    <label for="receipt_cc" class="block font-medium">CC opcional</label>
                    <textarea id="receipt_cc" name="receipt_cc" rows="3" class="mt-1 w-full border rounded" placeholder="coordinacion@empresa.com">{{ $ccRecipients }}</textarea>
                </div>

                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                    Se adjuntará el PDF actualizado y se agregará copia a info@haciendacinco.mx sin duplicarla.
                </div>

                <div class="flex flex-col-reverse sm:flex-row gap-2 sm:justify-end">
                    <a href="{{ route('transactions.show', $transaction) }}" class="px-4 py-3 bg-gray-200 rounded text-center">Cancelar</a>
                    <button class="px-4 py-3 bg-[#243834] text-white rounded font-semibold">
                        {{ $transaction->receiptEmailLogs->isEmpty() ? 'Enviar recibo' : 'Reenviar recibo' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
