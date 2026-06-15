<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Event;
use App\Support\SpanishMoney;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;

class EventContractGenerator
{
    public function generate(Event $event, array $data): Document
    {
        $event->loadMissing(['client', 'transactions']);

        $templatePath = storage_path('app/templates/contracts/contrato_arrendamiento_h5.docx');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException('No se encontró la plantilla del contrato en storage/app/templates/contracts/contrato_arrendamiento_h5.docx');
        }

        $template = new TemplateProcessor($templatePath);

        $total = (float) ($data['renta_total'] ?? $event->total_amount ?? 0);
        $anticipo = (float) ($data['anticipo_monto'] ?? 0);
        $segundoPago = (float) ($data['segundo_pago_monto'] ?? 0);
        $saldo = (float) ($data['saldo_monto'] ?? max($total - $anticipo - $segundoPago, 0));
        $deposito = (float) ($data['deposito_monto'] ?? 0);
        $horaExtra = (float) ($data['costo_hora_extra'] ?? 0);

        $values = [
            'arrendatario_nombre' => $data['arrendatario_nombre'] ?? $event->client?->full_name ?? '',
            'arrendatario_rfc' => $data['arrendatario_rfc'] ?? '',
            'arrendatario_domicilio' => $data['arrendatario_domicilio'] ?? '',
            'evento_tipo' => $data['evento_tipo'] ?? $event->event_type ?? '',
            'evento_fecha' => $data['evento_fecha'] ?? $event->event_date?->translatedFormat('d F Y') ?? '',
            'evento_personas' => $data['evento_personas'] ?? $event->guest_count ?? '',
            'evento_hora_inicio' => $data['evento_hora_inicio'] ?? '',
            'evento_hora_fin' => $data['evento_hora_fin'] ?? '',
            'evento_duracion' => $data['evento_duracion'] ?? '',
            'montaje_horario' => $data['montaje_horario'] ?? '',
            'desmontaje_horario' => $data['desmontaje_horario'] ?? '',
            'renta_total' => $this->money($total),
            'renta_total_letra' => SpanishMoney::toWords($total),
            'anticipo_monto' => $this->money($anticipo),
            'anticipo_monto_letra' => SpanishMoney::toWords($anticipo),
            'segundo_pago_monto' => $this->money($segundoPago),
            'segundo_pago_monto_letra' => SpanishMoney::toWords($segundoPago),
            'saldo_monto' => $this->money($saldo),
            'saldo_monto_letra' => SpanishMoney::toWords($saldo),
            'deposito_monto' => $this->money($deposito),
            'deposito_monto_letra' => SpanishMoney::toWords($deposito),
            'costo_hora_extra' => $this->money($horaExtra),
            'costo_hora_extra_letra' => SpanishMoney::toWords($horaExtra),
            'notas_contrato' => $data['notas_contrato'] ?? '',
            'clausulas_extra' => $this->formatExtraClauses($data['clausulas_extra'] ?? ''),
            'fecha_firma' => $data['fecha_firma'] ?? now()->translatedFormat('d \d\e F \d\e Y'),
        ];

        foreach ($values as $key => $value) {
            $template->setValue($key, $this->safeValue((string) $value));
        }

        $directory = 'documents/events/' . $event->id;
        Storage::disk('public')->makeDirectory($directory);

        $filename = 'contrato-arrendamiento-' . $event->id . '-' . Str::slug($event->client?->full_name ?? 'cliente') . '-' . now()->format('YmdHis') . '.docx';
        $relativePath = $directory . '/' . $filename;
        $absolutePath = Storage::disk('public')->path($relativePath);

        $template->saveAs($absolutePath);

        return Document::create([
            'client_id' => $event->client_id,
            'event_id' => $event->id,
            'uploaded_by' => auth()->id(),
            'category' => 'contract',
            'original_name' => 'Contrato de arrendamiento - ' . ($event->client?->full_name ?? 'Cliente') . '.docx',
            'file_path' => $relativePath,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size' => filesize($absolutePath),
            'notes' => 'Contrato generado automáticamente desde el evento.',
        ]);
    }

    private function money(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }

    private function safeValue(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function formatExtraClauses(string $clauses): string
    {
        $clauses = trim($clauses);

        if ($clauses === '') {
            return '';
        }

        return "CLÁUSULAS ADICIONALES\n" . $clauses;
    }
}
