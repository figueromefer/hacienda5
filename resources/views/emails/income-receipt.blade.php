<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Confirmación de abono</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#243834;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:28px 0;">
        <tr>
            <td align="center">
                <table width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:#243834;padding:26px 30px;color:#ffffff;">
                            <div style="font-size:20px;font-weight:700;letter-spacing:.14em;">HACIENDA CINCO</div>
                            <div style="font-size:12px;letter-spacing:.28em;margin-top:4px;opacity:.9;">LA VICTORIA</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px;">
                            <h1 style="margin:0 0 14px;font-size:22px;color:#243834;">Confirmación de abono</h1>

                            <p style="font-size:15px;line-height:1.7;margin:0 0 18px;color:#374151;">
                                Hola {{ $client?->full_name ?? 'cliente' }},<br>
                                confirmamos la recepción de tu abono por:
                            </p>

                            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:14px;padding:18px;margin:20px 0;">
                                <div style="font-size:13px;color:#6b7280;text-transform:uppercase;">Monto recibido</div>
                                <div style="font-size:28px;font-weight:800;color:#243834;margin-top:4px;">
                                    ${{ number_format($transaction->amount, 2) }}
                                </div>
                                <div style="font-size:13px;color:#6b7280;margin-top:4px;">{{ $amountInWords }}</div>
                            </div>

                            <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#374151;line-height:1.7;">
                                <tr>
                                    <td style="padding:4px 0;width:150px;color:#6b7280;">Recibo</td>
                                    <td style="padding:4px 0;font-weight:700;">#{{ $transaction->id }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;color:#6b7280;">Referencia</td>
                                    <td style="padding:4px 0;font-weight:700;">{{ $transaction->reference ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;color:#6b7280;">Fecha</td>
                                    <td style="padding:4px 0;">{{ $transaction->transaction_date?->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;color:#6b7280;">Concepto</td>
                                    <td style="padding:4px 0;">{{ $transaction->category ?? 'Abono' }}</td>
                                </tr>
                                @if($event)
                                    <tr>
                                        <td style="padding:4px 0;color:#6b7280;">Evento</td>
                                        <td style="padding:4px 0;">{{ $event->title }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:4px 0;color:#6b7280;">Fecha del evento</td>
                                        <td style="padding:4px 0;">{{ $event->event_date?->format('d/m/Y') }}</td>
                                    </tr>
                                @endif
                            </table>

                            <p style="font-size:15px;line-height:1.7;margin:22px 0 0;color:#374151;">
                                Adjuntamos el PDF de tu recibo. También puedes validar su autenticidad desde el siguiente enlace:
                            </p>

                            @if($publicUrl)
                                <p style="margin:18px 0 0;">
                                    <a href="{{ $publicUrl }}" style="display:inline-block;background:#243834;color:#ffffff !important;text-decoration:none;border-radius:10px;padding:12px 18px;font-size:14px;font-weight:700;">
                                        Validar recibo
                                    </a>
                                </p>
                                <p style="font-size:12px;line-height:1.5;color:#6b7280;word-break:break-all;margin:14px 0 0;">{{ $publicUrl }}</p>
                            @endif

                            <p style="font-size:14px;line-height:1.6;margin:26px 0 0;color:#6b7280;">
                                Gracias por tu confianza.<br>
                                <strong>Hacienda Cinco La Victoria</strong>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
