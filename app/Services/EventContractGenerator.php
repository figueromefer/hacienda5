<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Event;
use App\Support\SpanishDate;
use App\Support\SpanishMoney;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use ZipArchive;

class EventContractGenerator
{
    public const EXPECTED_PLACEHOLDERS = [
        'arrendatario_nombre',
        'arrendatario_rfc',
        'arrendatario_domicilio',
        'evento_tipo',
        'evento_fecha',
        'evento_personas',
        'evento_hora_inicio',
        'evento_hora_fin',
        'evento_duracion',
        'montaje_horario',
        'desmontaje_horario',
        'renta_total',
        'renta_total_letra',
        'anticipo_monto',
        'anticipo_monto_letra',
        'segundo_pago_monto',
        'segundo_pago_monto_letra',
        'saldo_monto',
        'saldo_monto_letra',
        'deposito_monto',
        'deposito_monto_letra',
        'costo_hora_extra',
        'costo_hora_extra_letra',
        'notas_contrato',
        'clausulas_extra',
        'fecha_firma',
        'arrendador_firma_nombre',
        'arrendatario_firma_nombre',
        'testigo_1_nombre',
        'testigo_2_nombre',
    ];

    public function generate(Event $event, array $data): Document
    {
        $event->loadMissing(['client', 'transactions']);

        $templatePath = (string) config('contracts.template_path');

        if (! is_file($templatePath)) {
            throw new RuntimeException('No se encontró la plantilla del contrato. Contacta al administrador antes de volver a intentarlo.');
        }

        $template = new TemplateProcessor($templatePath);
        $values = $this->values($event, $data);

        $this->validateTemplate($template, array_keys($values));

        foreach ($values as $key => $value) {
            $template->setValue($key, $this->safeValue((string) $value));
        }

        $disk = Storage::disk('public');
        $directory = 'documents/events/'.$event->id;
        $disk->makeDirectory($directory);

        $identifier = (string) Str::uuid();
        $filename = 'contrato-arrendamiento-'.$event->id.'-'.Str::slug($event->client?->full_name ?? 'cliente').'-'.$identifier.'.docx';
        $relativePath = $directory.'/'.$filename;
        $temporaryPath = $directory.'/.tmp-'.$identifier.'.docx';
        $absoluteTemporaryPath = $disk->path($temporaryPath);

        try {
            $template->saveAs($absoluteTemporaryPath);
            $this->validateGeneratedDocument($absoluteTemporaryPath);

            $disk->move($temporaryPath, $relativePath);

            try {
                return DB::transaction(fn (): Document => Document::create([
                    'client_id' => $event->client_id,
                    'event_id' => $event->id,
                    'uploaded_by' => auth()->id(),
                    'category' => 'contract',
                    'original_name' => 'Contrato de arrendamiento - '.($event->client?->full_name ?? 'Cliente').'.docx',
                    'file_path' => $relativePath,
                    'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'file_size' => $disk->size($relativePath),
                    'notes' => 'Contrato generado automáticamente desde el evento.',
                ]));
            } catch (\Throwable $exception) {
                $disk->delete($relativePath);

                throw $exception;
            }
        } finally {
            $disk->delete($temporaryPath);
        }
    }

    private function values(Event $event, array $data): array
    {
        $total = (float) ($data['renta_total'] ?? $event->total_amount ?? 0);
        $anticipo = (float) ($data['anticipo_monto'] ?? 0);
        $segundoPago = (float) ($data['segundo_pago_monto'] ?? 0);
        $saldo = (float) ($data['saldo_monto'] ?? max($total - $anticipo - $segundoPago, 0));
        $deposito = (float) ($data['deposito_monto'] ?? 0);
        $horaExtra = (float) ($data['costo_hora_extra'] ?? 0);
        $arrendatarioNombre = $data['arrendatario_nombre'] ?? $event->client?->full_name ?? '';

        return [
            'arrendatario_nombre' => $arrendatarioNombre,
            'arrendatario_rfc' => $data['arrendatario_rfc'] ?? '',
            'arrendatario_domicilio' => $data['arrendatario_domicilio'] ?? '',
            'evento_tipo' => $data['evento_tipo'] ?? $event->event_type ?? '',
            'evento_fecha' => SpanishDate::legal($data['evento_fecha'] ?? $event->event_date),
            'evento_personas' => $data['evento_personas'] ?? $event->guest_count ?? '',
            'evento_hora_inicio' => $data['evento_hora_inicio'] ?? $event->start_time ?? '',
            'evento_hora_fin' => $data['evento_hora_fin'] ?? $event->end_time ?? '',
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
            'fecha_firma' => SpanishDate::legal($data['fecha_firma'] ?? now()),
            'arrendador_firma_nombre' => $data['arrendador_firma_nombre'] ?? 'AGUILAR GANDARA INMOBILIARIA SA DE CV REP POR EL SR. JESUS MANUEL AGUILAR GANDARA.',
            'arrendatario_firma_nombre' => $data['arrendatario_firma_nombre'] ?? $arrendatarioNombre,
            'testigo_1_nombre' => $data['testigo_1_nombre'] ?? '',
            'testigo_2_nombre' => $data['testigo_2_nombre'] ?? '',
        ];
    }

    private function validateTemplate(TemplateProcessor $template, array $expected): void
    {
        $available = array_values(array_unique($template->getVariables()));
        $missing = array_values(array_diff($expected, $available));
        $unknown = array_values(array_diff($available, $expected));

        if ($missing !== []) {
            throw new RuntimeException('La plantilla del contrato está incompleta. Faltan los placeholders: '.implode(', ', $missing).'.');
        }

        if ($unknown !== []) {
            throw new RuntimeException('La plantilla contiene placeholders no reconocidos: '.implode(', ', $unknown).'.');
        }
    }

    private function validateGeneratedDocument(string $path): void
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('El contrato generado no es un archivo DOCX válido. No se registró ningún documento.');
        }

        if ($zip->locateName('[Content_Types].xml') === false || $zip->locateName('word/document.xml') === false) {
            $zip->close();

            throw new RuntimeException('El contrato generado no es un archivo DOCX válido. No se registró ningún documento.');
        }

        $remaining = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if (! is_string($name) || ! str_starts_with($name, 'word/') || ! str_ends_with($name, '.xml')) {
                continue;
            }

            $contents = $zip->getFromIndex($index);

            if (is_string($contents) && preg_match_all('/\$\{([^}]+)\}/u', $contents, $matches)) {
                $remaining = array_merge($remaining, $matches[1]);
            }
        }

        $zip->close();
        $remaining = array_values(array_unique($remaining));

        if ($remaining !== []) {
            throw new RuntimeException('El contrato conserva placeholders sin sustituir: '.implode(', ', $remaining).'. No se registró ningún documento.');
        }
    }

    private function money(float $amount): string
    {
        return '$'.number_format($amount, 2);
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

        return "CLÁUSULAS ADICIONALES\n".$clauses;
    }
}
