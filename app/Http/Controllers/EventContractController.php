<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventContractGenerator;
use Illuminate\Http\Request;

class EventContractController extends Controller
{
    public function create(Event $event)
    {
        $event->load(['client', 'transactions']);

        $paidIncome = $event->transactions
            ->where('type', 'income')
            ->where('status', 'paid')
            ->sum('amount');

        $total = (float) ($event->total_amount ?? 0);
        $saldo = max($total - (float) $paidIncome, 0);

        return view('events.contracts.create', [
            'event' => $event,
            'client' => $event->client,
            'paidIncome' => $paidIncome,
            'saldo' => $saldo,
        ]);
    }

    public function store(Request $request, Event $event, EventContractGenerator $generator)
    {
        $data = $request->validate([
            'arrendatario_nombre' => ['required', 'string', 'max:255'],
            'arrendatario_rfc' => ['nullable', 'string', 'max:50'],
            'arrendatario_domicilio' => ['nullable', 'string', 'max:1000'],
            'evento_tipo' => ['required', 'string', 'max:255'],
            'evento_fecha' => ['required', 'string', 'max:255'],
            'evento_personas' => ['nullable', 'string', 'max:255'],
            'evento_hora_inicio' => ['nullable', 'string', 'max:255'],
            'evento_hora_fin' => ['nullable', 'string', 'max:255'],
            'evento_duracion' => ['nullable', 'string', 'max:255'],
            'montaje_horario' => ['nullable', 'string', 'max:1000'],
            'desmontaje_horario' => ['nullable', 'string', 'max:1000'],
            'renta_total' => ['required', 'numeric', 'min:0'],
            'anticipo_monto' => ['nullable', 'numeric', 'min:0'],
            'segundo_pago_monto' => ['nullable', 'numeric', 'min:0'],
            'saldo_monto' => ['nullable', 'numeric', 'min:0'],
            'deposito_monto' => ['nullable', 'numeric', 'min:0'],
            'costo_hora_extra' => ['nullable', 'numeric', 'min:0'],
            'notas_contrato' => ['nullable', 'string'],
            'clausulas_extra' => ['nullable', 'string'],
            'fecha_firma' => ['nullable', 'string', 'max:255'],
            'arrendador_firma_nombre' => ['nullable', 'string', 'max:500'],
            'arrendatario_firma_nombre' => ['required', 'string', 'max:255'],
            'testigo_1_nombre' => ['nullable', 'string', 'max:255'],
            'testigo_2_nombre' => ['nullable', 'string', 'max:255'],
        ]);

        $document = $generator->generate($event, $data);

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Contrato generado correctamente y agregado a documentos del evento: ' . $document->original_name);
    }
}
