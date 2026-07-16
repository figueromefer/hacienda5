<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 34px 42px 44px; }
        * { box-sizing: border-box; }
        body { color: #24312e; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; margin: 0; }
        .header { border-bottom: 2px solid #9b7b45; padding-bottom: 15px; width: 100%; }
        .logo { height: 82px; object-fit: contain; width: 94px; }
        .brand { color: #243834; font-size: 20px; font-weight: bold; letter-spacing: 1px; margin: 0; text-transform: uppercase; }
        .tagline { color: #8a6d3b; font-size: 9px; letter-spacing: 2px; margin-top: 3px; text-transform: uppercase; }
        .document-title { color: #243834; font-size: 18px; font-weight: bold; margin: 0; text-align: right; }
        .folio { color: #8a6d3b; font-size: 11px; margin-top: 5px; text-align: right; }
        .intro { margin-top: 20px; width: 100%; }
        .panel { background: #f4f6f5; border-left: 3px solid #9b7b45; padding: 11px 13px; vertical-align: top; width: 49%; }
        .panel-title { color: #8a6d3b; font-size: 8px; font-weight: bold; letter-spacing: 1px; margin-bottom: 7px; text-transform: uppercase; }
        .primary { color: #243834; font-size: 12px; font-weight: bold; margin-bottom: 4px; }
        .muted { color: #64716d; }
        .items { border-collapse: collapse; margin-top: 22px; width: 100%; }
        .items thead { display: table-header-group; }
        .items tr { page-break-inside: avoid; }
        .items th { background: #243834; color: #fff; font-size: 8px; letter-spacing: .6px; padding: 9px 8px; text-align: left; text-transform: uppercase; }
        .items td { border-bottom: 1px solid #dce2df; padding: 9px 8px; vertical-align: top; }
        .number { text-align: right !important; white-space: nowrap; }
        .summary { margin-left: auto; margin-top: 18px; page-break-inside: avoid; width: 285px; }
        .summary td { border: 0; padding: 5px 8px; }
        .summary .grand-total td { background: #243834; color: #fff; font-size: 13px; font-weight: bold; padding: 9px 8px; }
        .notes { background: #f4f6f5; border-top: 1px solid #dce2df; margin-top: 22px; padding: 12px 14px; page-break-inside: avoid; }
        .footer { bottom: -27px; color: #7b8581; font-size: 8px; left: 0; position: fixed; right: 0; text-align: center; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 105px">
                @if(!empty($logoPath) && file_exists($logoPath))
                    <img src="{{ $logoPath }}" class="logo" alt="Hacienda Cinco">
                @endif
            </td>
            <td>
                <p class="brand">Hacienda Cinco</p>
                <p class="tagline">La Victoria · Eventos</p>
            </td>
            <td>
                <p class="document-title">Cotización</p>
                <p class="folio">{{ $quotation->folio }}</p>
            </td>
        </tr>
    </table>

    <table class="intro">
        <tr>
            <td class="panel">
                <div class="panel-title">Preparada para</div>
                <div class="primary">{{ $quotation->client->full_name }}</div>
                @if($quotation->client->company_name)<div>{{ $quotation->client->company_name }}</div>@endif
                @if($quotation->client->email)<div class="muted">{{ $quotation->client->email }}</div>@endif
                @if($quotation->client->phone)<div class="muted">{{ $quotation->client->phone }}</div>@endif
            </td>
            <td style="width: 2%"></td>
            <td class="panel">
                <div class="panel-title">Evento y vigencia</div>
                <div class="primary">{{ $quotation->event?->title ?? 'Sin evento asociado' }}</div>
                @if($quotation->event)
                    <div>{{ $quotation->event->event_date?->format('d/m/Y') ?? 'Fecha por definir' }} · {{ $quotation->event->event_type }}</div>
                @endif
                <div class="muted">Estado: {{ $quotation->status_label }}</div>
                <div class="muted">Válida hasta: {{ $quotation->valid_until?->format('d/m/Y') ?? 'Sin vigencia definida' }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="number" style="width: 70px">Cantidad</th>
                <th class="number" style="width: 105px">Precio unitario</th>
                <th class="number" style="width: 105px">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="number">{{ $item->quantity }}</td>
                    <td class="number">$ {{ number_format($item->unit_price, 2) }}</td>
                    <td class="number">$ {{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr><td>Subtotal</td><td class="number">$ {{ number_format($quotation->subtotal, 2) }}</td></tr>
        <tr><td>Descuento</td><td class="number">$ {{ number_format($quotation->discount, 2) }}</td></tr>
        <tr class="grand-total"><td>Total</td><td class="number">$ {{ number_format($quotation->total, 2) }}</td></tr>
    </table>

    @if($quotation->notes)
        <div class="notes"><strong>Notas</strong><br>{{ $quotation->notes }}</div>
    @endif

    <div class="footer">Hacienda Cinco La Victoria · Cotización {{ $quotation->folio }}</div>
</body>
</html>
