<?php

namespace Tests\Feature;

use App\Services\EventContractGenerator;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class ValidateContractTemplateCommandTest extends TestCase
{
    private array $temporaryTemplates = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryTemplates as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_valid_template_reports_expected_summary_and_is_not_modified(): void
    {
        $path = $this->template();
        $hashBefore = hash_file('sha256', $path);

        $this->artisan('contracts:validate-template', ['path' => $path])
            ->expectsOutputToContain('Placeholders detectados:')
            ->expectsOutputToContain('${evento_fecha}')
            ->expectsOutputToContain('30 placeholders esperados.')
            ->expectsOutputToContain('30 encontrados.')
            ->expectsOutputToContain('0 faltantes.')
            ->expectsOutputToContain('0 desconocidos.')
            ->expectsOutputToContain('Plantilla válida.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame($hashBefore, hash_file('sha256', $path));
    }

    public function test_default_configured_path_is_used_when_argument_is_omitted(): void
    {
        $path = $this->template();
        config()->set('contracts.template_path', $path);

        $this->artisan('contracts:validate-template')
            ->expectsOutputToContain('Plantilla: '.$path)
            ->expectsOutputToContain('Plantilla válida.')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_invalid_template_lists_missing_and_unknown_placeholders_and_fails(): void
    {
        $path = $this->template(['fecha_firma'], ['placeholder_inexistente']);

        $this->artisan('contracts:validate-template', ['path' => $path])
            ->expectsOutputToContain('30 placeholders esperados.')
            ->expectsOutputToContain('30 encontrados.')
            ->expectsOutputToContain('1 faltantes.')
            ->expectsOutputToContain('1 desconocidos.')
            ->expectsOutputToContain('${fecha_firma}')
            ->expectsOutputToContain('${placeholder_inexistente}')
            ->expectsOutputToContain('Plantilla no válida.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_missing_file_fails_with_clear_message(): void
    {
        $this->artisan('contracts:validate-template', ['path' => '/tmp/plantilla-que-no-existe.docx'])
            ->expectsOutputToContain('Plantilla no válida: el archivo no existe o no se puede leer.')
            ->assertExitCode(Command::FAILURE);
    }

    private function template(array $omitted = [], array $additional = []): string
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $placeholders = array_merge(EventContractGenerator::EXPECTED_PLACEHOLDERS, $additional);

        foreach ($placeholders as $placeholder) {
            if (in_array($placeholder, $omitted, true)) {
                continue;
            }

            $section->addText('${'.$placeholder.'}');
        }

        $path = tempnam(sys_get_temp_dir(), 'h5-contract-audit-');
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);
        $this->temporaryTemplates[] = $path;

        return $path;
    }
}
