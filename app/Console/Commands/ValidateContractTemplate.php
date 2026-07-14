<?php

namespace App\Console\Commands;

use App\Services\EventContractGenerator;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class ValidateContractTemplate extends Command
{
    protected $signature = 'contracts:validate-template
                            {path? : Ruta opcional de la plantilla DOCX}';

    protected $description = 'Valida los placeholders de la plantilla DOCX de contratos sin modificarla';

    public function handle(): int
    {
        $path = (string) ($this->argument('path') ?: config('contracts.template_path'));
        $expected = EventContractGenerator::EXPECTED_PLACEHOLDERS;

        $this->line('Plantilla: '.$path);

        if (! is_file($path) || ! is_readable($path)) {
            $this->error('Plantilla no válida: el archivo no existe o no se puede leer.');

            return self::FAILURE;
        }

        try {
            $detected = array_values(array_unique((new TemplateProcessor($path))->getVariables()));
        } catch (Throwable $exception) {
            $this->error('Plantilla no válida: no fue posible abrir el archivo como DOCX.');
            $this->line('Detalle: '.$exception->getMessage());

            return self::FAILURE;
        }

        sort($detected);

        $missing = array_values(array_diff($expected, $detected));
        $unknown = array_values(array_diff($detected, $expected));

        $this->newLine();
        $this->line('Placeholders detectados:');

        if ($detected === []) {
            $this->line('  (ninguno)');
        } else {
            foreach ($detected as $placeholder) {
                $this->line('  ${'.$placeholder.'}');
            }
        }

        $this->newLine();
        $this->line(count($expected).' placeholders esperados.');
        $this->line(count($detected).' encontrados.');
        $this->line(count($missing).' faltantes.');
        $this->line(count($unknown).' desconocidos.');

        if ($missing !== []) {
            $this->newLine();
            $this->error('Faltantes:');

            foreach ($missing as $placeholder) {
                $this->line('  ${'.$placeholder.'}');
            }
        }

        if ($unknown !== []) {
            $this->newLine();
            $this->error('Desconocidos:');

            foreach ($unknown as $placeholder) {
                $this->line('  ${'.$placeholder.'}');
            }
        }

        $this->newLine();

        if ($missing !== [] || $unknown !== []) {
            $this->error('Plantilla no válida.');

            return self::FAILURE;
        }

        $this->info('Plantilla válida.');

        return self::SUCCESS;
    }
}
