<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page{margin:0}body{font-family:DejaVu Sans,sans-serif;margin:0;color:#222;background:#fff}.header{background:#243834;padding:14px 30px;color:#fff;height:72px}.logo{width:82px;float:left}.brand{float:left;margin-left:16px;margin-top:20px;letter-spacing:4px;font-size:15px}.brand small{display:block;letter-spacing:6px;font-size:9px;margin-top:3px}.wrap{padding:20px 42px}.cancelled{margin-bottom:12px;border:2px solid #991b1b;background:#fef2f2;color:#991b1b;padding:8px;text-align:center;font-size:12px;font-weight:bold}.date{text-align:left;margin-bottom:16px;font-size:11px;text-transform:uppercase}.title{text-align:center;font-size:18px;font-weight:bold;margin-bottom:18px;letter-spacing:.4px}.text{font-size:13px;line-height:1.55;text-align:justify}.signature{margin-top:42px;text-align:center;font-size:12px;font-weight:bold}.line{width:280px;border-top:1px solid #222;margin:0 auto 7px}.policy{margin-top:25px;font-size:9px;line-height:1.4;text-align:justify;font-weight:bold}.meta{margin-top:10px;font-size:9px;color:#555}.notes{margin-top:12px;font-size:10px;line-height:1.35}.clear{clear:both}.verification{margin-top:16px;border:1px solid #ddd;border-radius:8px;padding:9px;background:#fafafa;page-break-inside:avoid}.qr{width:62px;float:left;margin-right:12px}.verify-title{font-size:10px;font-weight:bold;color:#243834;text-transform:uppercase}.verify-url{font-size:8px;color:#555;word-break:break-all;margin-top:4px}.verify-copy{font-size:9px;color:#555;line-height:1.3;margin-top:3px}
    </style>
</head>
<body>
    @php
        $isIncome = $transaction->type === \App\Models\Transaction::TYPE_INCOME;
        $clientName = $transaction->client?->full_name ?? 'CLIENTE NO ASIGNADO';
        $event = $transaction->event;
        $concept = $transaction->category ?: ($transaction->notes ?: 'Movimiento registrado');
        $signer = $isIncome ? 'ALEJANDRO AGUILAR GANDARA' : 'RECIBI DE CONFORMIDAD';
        $qrSvg = !empty($publicUrl) ? base64_encode(QrCode::format('svg')->size(150)->margin(1)->generate($publicUrl)) : null;
    @endphp

    <div class="header">
        @if(!empty($logoPath) && file_exists($logoPath))
            <img src="{{ $logoPath }}" class="logo">
        @endif
        <div class="brand">HACIENDA CINCO<small>LA VICTORIA</small></div>
        <div class="clear"></div>
    </div>

    <div class="wrap">
        @if($transaction->status === \App\Models\Transaction::STATUS_CANCELLED)
            <div class="cancelled">DOCUMENTO CANCELADO — NO ES UN RECIBO VIGENTE</div>
        @endif
        <div class="date">{{ $transaction->transaction_date?->locale('es')->translatedFormat('d-F-Y') }}</div>
        <div class="title">{{ $receiptTitle }}</div>

        @if($isIncome)
            <div class="text">
                Recibi la cantidad de <strong>${{ number_format($transaction->amount, 2) }}</strong>
                (<strong>{{ $amountInWords }}</strong>),
                de <strong>{{ mb_strtoupper($clientName) }}</strong>
                concepto de "<strong>{{ mb_strtoupper($concept) }}</strong>"
                @if($event)
                    para <strong>{{ mb_strtoupper($event->event_type ?: 'EVENTO') }}</strong>,
                    a llevarse a cabo <strong>{{ $event->event_date?->locale('es')->translatedFormat('d-M-Y') }}</strong>.
                    @if($event->guest_count)
                        <strong>{{ $event->guest_count }} PERSONAS</strong>.
                    @endif
                @endif
            </div>
        @else
            <div class="text">
                Recibi la cantidad de <strong>${{ number_format($transaction->amount, 2) }}</strong>
                (<strong>{{ $amountInWords }}</strong>),
                de <strong>{{ mb_strtoupper($clientName) }}</strong>
                concepto "<strong>{{ mb_strtoupper($concept) }}</strong>".
            </div>
        @endif

        @if($transaction->notes && $transaction->category)
            <div class="notes"><strong>Notas:</strong> {{ $transaction->notes }}</div>
        @endif

        <div class="signature">
            <div class="line"></div>
            {{ mb_strtoupper($signer) }}
        </div>

        @if($isIncome)
            <div class="policy">
                POLITICA DE CANCELACION. EL CLIENTE CUENTA CON 5 DIAS NATURALES A PARTIR DEL SIGUIENTE APARTADO PARA CANCELAR EL EVENTO SIN PENALIZACION, POSTERIORMENTE SE SUJETARA A NUESTRAS POLITICAS DE CANCELACION LAS CUALES LLEVAN PENALIZACION.
            </div>
        @endif

        @if($publicUrl && $qrSvg)
            <div class="verification">
                <img class="qr" src="data:image/svg+xml;base64,{{ $qrSvg }}">
                <div class="verify-title">Verificacion de autenticidad</div>
                <div class="verify-copy">Escanea este codigo QR para consultar el recibo real registrado en el sistema de Hacienda Cinco.</div>
                <div class="verify-url">{{ $publicUrl }}</div>
                <div class="clear"></div>
            </div>
        @endif

        <div class="meta">Recibo #{{ $transaction->id }} · Método: {{ $transaction->method_label }} · Estatus: {{ $transaction->status_label }} · Referencia: {{ $transaction->reference ?: '-' }}</div>
    </div>
</body>
</html>
