<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:DejaVu Sans, sans-serif;margin:0;color:#222;background:#fff}.header{background:#243834;padding:22px 34px;color:#fff;height:110px}.logo{width:120px;float:left}.brand{float:left;margin-left:20px;margin-top:35px;letter-spacing:5px;font-size:18px}.brand small{display:block;letter-spacing:8px;font-size:12px;margin-top:4px}.wrap{padding:42px 58px}.date{text-align:left;margin-bottom:42px;font-size:15px;text-transform:uppercase}.title{text-align:center;font-size:22px;font-weight:bold;margin-bottom:36px;letter-spacing:.5px}.text{font-size:17px;line-height:1.85;text-align:justify}.signature{margin-top:90px;text-align:center;font-weight:bold}.line{width:320px;border-top:1px solid #222;margin:0 auto 10px}.policy{margin-top:65px;font-size:12px;line-height:1.6;text-align:justify;font-weight:bold}.meta{margin-top:24px;font-size:12px;color:#555}.notes{margin-top:25px;font-size:13px;line-height:1.5}.clear{clear:both}.verification{margin-top:34px;border:1px solid #ddd;border-radius:10px;padding:14px;background:#fafafa}.qr{width:92px;float:left;margin-right:16px}.verify-title{font-size:12px;font-weight:bold;color:#243834;text-transform:uppercase}.verify-url{font-size:10px;color:#555;word-break:break-all;margin-top:6px}.verify-copy{font-size:11px;color:#555;line-height:1.4;margin-top:5px}
    </style>
</head>
<body>
    @php
        $isIncome = $transaction->type === \App\Models\Transaction::TYPE_INCOME;
        $clientName = $transaction->client?->full_name ?? 'CLIENTE NO ASIGNADO';
        $event = $transaction->event;
        $concept = $transaction->category ?: ($transaction->notes ?: 'Movimiento registrado');
        $signer = $isIncome ? 'ALEJANDRO AGUILAR GANDARA' : ($transaction->reference ?: 'RECIBI DE CONFORMIDAD');
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

        <div class="meta">Recibo #{{ $transaction->id }} · Metodo: {{ $transaction->method ?: '-' }} · Referencia: {{ $transaction->reference ?: '-' }}</div>
    </div>
</body>
</html>
