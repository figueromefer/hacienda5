<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { color: #222; font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 36px; }
        h1 { color: #243834; font-size: 24px; margin: 0 0 6px; }
        .meta { margin-bottom: 24px; }
        .meta div { margin-bottom: 5px; }
        table { border-collapse: collapse; margin-top: 18px; width: 100%; }
        th, td { border-bottom: 1px solid #ddd; padding: 9px 7px; }
        th { background: #243834; color: #fff; text-align: left; }
        .number { text-align: right; }
        .totals { margin-left: auto; margin-top: 20px; width: 280px; }
        .totals td { border: 0; padding: 4px 7px; }
        .total td { border-top: 1px solid #222; font-size: 14px; font-weight: bold; padding-top: 8px; }
        .notes { background: #f7f7f7; margin-top: 28px; padding: 12px; }
    </style>
</head>
<body>
    <h1>Cotización {{ $quotation->folio }}</h1>

    <div class="meta">
        <div><strong>Cliente:</strong> {{ $quotation->client->full_name }}</div>
        <div><strong>Evento:</strong> {{ $quotation->event?->title ?? 'Sin evento' }}</div>
        <div><strong>Estatus:</strong> {{ $quotation->status }}</div>
        <div><strong>Válida hasta:</strong> {{ $quotation->valid_until?->format('d/m/Y') ?? '-' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="number">Cantidad</th>
                <th class="number">Precio unitario</th>
                <th class="number">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="number">{{ $item->quantity }}</td>
                    <td class="number">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="number">${{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="number">${{ number_format($quotation->subtotal, 2) }}</td></tr>
        <tr><td>Descuento</td><td class="number">${{ number_format($quotation->discount, 2) }}</td></tr>
        <tr class="total"><td>Total</td><td class="number">${{ number_format($quotation->total, 2) }}</td></tr>
    </table>

    @if($quotation->notes)
        <div class="notes"><strong>Notas:</strong><br>{{ $quotation->notes }}</div>
    @endif
</body>
</html>
