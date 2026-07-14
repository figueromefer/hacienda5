<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Recibo #{{ $transaction->id }}</h2>
                <p class="text-sm text-gray-500 mt-1">Vista administrativa del recibo registrado en sistema.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('transactions.index') }}" style="display:inline-flex;align-items:center;border-radius:10px;background:#e5e7eb;color:#1f2937;padding:10px 16px;font-weight:600;text-decoration:none;">Volver</a>
                <a href="{{ route('transactions.edit', $transaction) }}" style="display:inline-flex;align-items:center;border-radius:10px;background:#fffbeb;color:#b45309;padding:10px 16px;font-weight:600;text-decoration:none;">Editar</a>
                @if($transaction->receipt_token)
                    <a href="{{ route('receipts.public.show', $transaction->receipt_token) }}" target="_blank" style="display:inline-flex;align-items:center;border-radius:10px;background:#ecfdf5;color:#047857;padding:10px 16px;font-weight:600;text-decoration:none;">Validar</a>
                @endif
                <a href="{{ route('transactions.pdf', $transaction) }}" style="display:inline-flex;align-items:center;border-radius:10px;background:#243834;color:#fff !important;padding:10px 16px;font-weight:600;text-decoration:none;">Descargar PDF</a>
            </div>
        </div>
    </x-slot>

    @php
        $isIncome = $transaction->type === \App\Models\Transaction::TYPE_INCOME;
        $clientName = $transaction->client?->full_name ?? 'SIN CLIENTE';
        $event = $transaction->event;
        $concept = $transaction->category ?: ($transaction->notes ?: 'Movimiento registrado');
        $signer = $isIncome ? 'ALEJANDRO AGUILAR GANDARA' : 'RECIBI DE CONFORMIDAD';
    @endphp

    <div style="padding:32px 16px;">
        <div style="max-width:920px;margin:0 auto;">
            <div style="background:#fff;border-radius:24px;box-shadow:0 20px 45px rgba(0,0,0,.08);overflow:hidden;">
                <div class="receipt-brand-header" style="background:#243834;padding:24px 32px;display:flex;align-items:center;gap:22px;">
                    <img src="{{ asset('images/hacienda-cinco-logo.png') }}" alt="Hacienda Cinco" style="width:96px !important;max-width:96px !important;height:auto !important;display:block !important;flex:0 0 96px !important;object-fit:contain !important;">
                    <div style="color:#fff;line-height:1.3;">
                        <div style="letter-spacing:.32em;font-size:18px;font-weight:600;">HACIENDA CINCO</div>
                        <div style="letter-spacing:.38em;font-size:12px;opacity:.9;margin-top:4px;">LA VICTORIA</div>
                    </div>
                </div>

                <div class="receipt-body" style="padding:32px;">
                    <div class="receipt-summary-grid" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:28px;">
                        <div style="border:1px solid #e5e7eb;background:#f9fafb;border-radius:16px;padding:16px;">
                            <div style="font-size:12px;text-transform:uppercase;color:#6b7280;">Fecha</div>
                            <div style="font-weight:700;margin-top:4px;color:#374151;">{{ $transaction->transaction_date?->format('d/m/Y') }}</div>
                        </div>
                        <div style="border:1px solid #e5e7eb;background:#f9fafb;border-radius:16px;padding:16px;">
                            <div style="font-size:12px;text-transform:uppercase;color:#6b7280;">Tipo</div>
                            <div style="font-weight:700;margin-top:4px;color:#374151;">{{ $transaction->type_label }}</div>
                        </div>
                        <div style="border:1px solid #e5e7eb;background:#f9fafb;border-radius:16px;padding:16px;">
                            <div style="font-size:12px;text-transform:uppercase;color:#6b7280;">Estatus</div>
                            <div style="font-weight:700;margin-top:4px;color:#374151;">{{ $transaction->status }}</div>
                        </div>
                        <div style="border:1px solid #e5e7eb;background:#f9fafb;border-radius:16px;padding:16px;">
                            <div style="font-size:12px;text-transform:uppercase;color:#6b7280;">Referencia</div>
                            <div style="font-weight:700;margin-top:4px;color:#374151;word-break:break-word;">{{ $transaction->reference ?: '-' }}</div>
                        </div>
                    </div>

                    <h1 style="font-size:26px;font-weight:800;text-align:center;margin:0 0 28px;color:#243834;">{{ $receiptTitle }}</h1>

                    <div class="receipt-content" style="border:1px solid #e5e7eb;border-radius:20px;padding:28px;">
                        <p style="font-size:18px;line-height:1.9;text-align:justify;margin:0;color:#374151;">
                            @if($isIncome)
                                Recibí la cantidad de <strong>${{ number_format($transaction->amount, 2) }}</strong>
                                (<strong>{{ $amountInWords }}</strong>),
                                de <strong>{{ mb_strtoupper($clientName) }}</strong>
                                concepto de “<strong>{{ mb_strtoupper($concept) }}</strong>”
                                @if($event)
                                    para <strong>{{ mb_strtoupper($event->event_type ?: 'EVENTO') }}</strong>,
                                    a llevarse a cabo <strong>{{ $event->event_date?->format('d/m/Y') }}</strong>.
                                    @if($event->guest_count)
                                        <strong>{{ $event->guest_count }} PERSONAS</strong>.
                                    @endif
                                @endif
                            @else
                                Recibí la cantidad de <strong>${{ number_format($transaction->amount, 2) }}</strong>
                                (<strong>{{ $amountInWords }}</strong>),
                                de <strong>{{ mb_strtoupper($clientName) }}</strong>
                                concepto “<strong>{{ mb_strtoupper($concept) }}</strong>”.
                            @endif
                        </p>

                        @if($transaction->notes && $transaction->category)
                            <div style="margin-top:24px;border:1px solid #e5e7eb;background:#f9fafb;border-radius:14px;padding:16px;color:#374151;">
                                <strong>Notas:</strong> {{ $transaction->notes }}
                            </div>
                        @endif

                        <div style="margin-top:72px;text-align:center;font-weight:800;color:#374151;">
                            <div style="border-top:1px solid #374151;width:320px;max-width:100%;margin:0 auto 10px;"></div>
                            {{ mb_strtoupper($signer) }}
                        </div>

                        @if($isIncome)
                            <p style="margin-top:42px;font-size:13px;line-height:1.65;font-weight:800;text-align:justify;color:#374151;">
                                POLÍTICA DE CANCELACIÓN. EL CLIENTE CUENTA CON 5 DÍAS NATURALES A PARTIR DEL SIGUIENTE APARTADO PARA CANCELAR EL EVENTO SIN PENALIZACIÓN, POSTERIORMENTE SE SUJETARÁ A NUESTRAS POLÍTICAS DE CANCELACIÓN LAS CUALES LLEVAN PENALIZACIÓN.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            @if($transaction->receipt_token)
                <div class="receipt-verification" style="background:#fff;border-radius:20px;box-shadow:0 12px 30px rgba(0,0,0,.06);padding:24px;margin-top:24px;display:flex;gap:18px;align-items:center;justify-content:space-between;">
                    <div>
                        <h3 style="font-weight:800;color:#111827;margin:0;">Verificación pública</h3>
                        <p style="font-size:14px;color:#4b5563;margin:6px 0 0;">Esta URL es la fuente oficial para validar que el recibo no fue alterado.</p>
                        <div style="margin-top:8px;font-size:12px;color:#6b7280;word-break:break-all;">{{ route('receipts.public.show', $transaction->receipt_token) }}</div>
                    </div>
                    <a href="{{ route('receipts.public.show', $transaction->receipt_token) }}" target="_blank" style="display:inline-flex;white-space:nowrap;border-radius:10px;background:#ecfdf5;color:#047857;padding:10px 16px;font-weight:700;text-decoration:none;">Abrir validación</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
