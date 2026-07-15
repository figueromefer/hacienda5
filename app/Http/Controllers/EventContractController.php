<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventContractGenerator;
use App\Services\FinancialBalanceCalculator;
use App\Support\MoneyNormalizer;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class EventContractController extends Controller
{
    public function create(Event $event, FinancialBalanceCalculator $balanceCalculator)
    {
        $event->load(['client', 'transactions', 'quotations']);
        $financialBalance = $balanceCalculator->forEvent($event);

        return view('events.contracts.create', [
            'event' => $event,
            'client' => $event->client,
            'paidIncome' => $financialBalance['paid_income'],
            'eventCost' => $financialBalance['approved_quotation_total'],
            'saldo' => $financialBalance['pending_receivable'],
        ]);
    }

    public function store(Request $request, Event $event, EventContractGenerator $generator)
    {
        $request->merge(MoneyNormalizer::normalizeArray($request->all(), [
            'renta_total',
            'anticipo_monto',
            'segundo_pago_monto',
            'saldo_monto',
            'deposito_monto',
            'costo_hora_extra',
        ]));

        $data = $request->validate([
            'arrendatario_nombre' => ['required', 'string', 'max:255'],
            'arrendatario_rfc' => ['nullable', 'string', 'max:50'],
            'arrendatario_domicilio' => ['nullable', 'string', 'max:1000'],
            'evento_tipo' => ['required', 'string', 'max:255'],
            'evento_fecha' => ['required', 'date_format:Y-m-d'],
            'evento_personas' => ['nullable', 'string', 'max:255'],
            'evento_hora_inicio' => ['nullable', 'string', 'max:255'],
            'evento_hora_fin' => ['nullable', 'string', 'max:255'],
            'evento_duracion' => ['nullable', 'numeric', 'min:0'],
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
            'fecha_firma' => ['nullable', 'date_format:Y-m-d'],
            'arrendador_firma_nombre' => ['nullable', 'string', 'max:500'],
            'arrendatario_firma_nombre' => ['required', 'string', 'max:255'],
            'testigo_1_nombre' => ['nullable', 'string', 'max:255'],
            'testigo_2_nombre' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $document = $generator->generate($event, $data);
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['contract' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['contract' => 'No fue posible generar un contrato válido. No se registró ningún documento.']);
        }

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Contrato generado correctamente y agregado a documentos del evento: '.$document->original_name);
    }
}
