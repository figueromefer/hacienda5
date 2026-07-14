<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Event;
use App\Models\User;
use App\Services\EventContractGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class EventContractGeneratorTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_generated_contract_uses_spanish_dates_preserves_placeholder_style_and_registers_document(): void
    {
        Storage::fake('public');
        config()->set('contracts.template_path', $this->template());

        $user = User::factory()->create();
        $event = $this->event();

        $document = $this->actingAs($user)
            ->app
            ->make(EventContractGenerator::class)
            ->generate($event, $this->contractData());

        Storage::disk('public')->assertExists($document->file_path);
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'event_id' => $event->id,
            'client_id' => $event->client_id,
            'category' => 'contract',
            'uploaded_by' => $user->id,
        ]);

        $path = Storage::disk('public')->path($document->file_path);
        $zip = new ZipArchive;

        $this->assertTrue($zip->open($path));
        $this->assertNotFalse($zip->locateName('[Content_Types].xml'));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertIsString($xml);
        $this->assertStringContainsString('09 DE JULIO DE 2026', $xml);
        $this->assertStringContainsString('14 DE JULIO DE 2026', $xml);
        $this->assertStringNotContainsString('July', $xml);
        $this->assertStringNotContainsString('${', $xml);
        $datePosition = strpos($xml, '09 DE JULIO DE 2026');
        $runStart = strrpos(substr($xml, 0, $datePosition), '<w:r>');
        $runEnd = strpos($xml, '</w:r>', $datePosition);
        $dateRun = substr($xml, $runStart, $runEnd - $runStart);

        $this->assertStringContainsString('w:ascii="Arial"', $dateRun);
        $this->assertStringContainsString('<w:sz w:val="22"/>', $dateRun);
        $this->assertStringContainsString('<w:b w:val="1"/>', $dateRun);
    }

    public function test_incomplete_template_is_rejected_without_file_or_database_record(): void
    {
        Storage::fake('public');
        config()->set('contracts.template_path', $this->template(['fecha_firma']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Faltan los placeholders: fecha_firma');

        try {
            app(EventContractGenerator::class)->generate($this->event(), $this->contractData());
        } finally {
            $this->assertDatabaseCount('documents', 0);
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    public function test_generating_again_does_not_overwrite_previous_contract(): void
    {
        Storage::fake('public');
        config()->set('contracts.template_path', $this->template());

        $event = $this->event();
        $generator = app(EventContractGenerator::class);
        $first = $generator->generate($event, $this->contractData());
        $second = $generator->generate($event, $this->contractData());

        $this->assertNotSame($first->file_path, $second->file_path);
        Storage::disk('public')->assertExists([$first->file_path, $second->file_path]);
        $this->assertDatabaseCount('documents', 2);
    }

    private function template(array $omitted = []): string
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();

        foreach (EventContractGenerator::EXPECTED_PLACEHOLDERS as $placeholder) {
            if (in_array($placeholder, $omitted, true)) {
                continue;
            }

            $textRun = $section->addTextRun();
            $textRun->addText('${'.$placeholder.'}', [
                'name' => 'Arial',
                'size' => 11,
                'bold' => true,
            ]);
        }

        $path = tempnam(sys_get_temp_dir(), 'h5-contract-template-');
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);
        $this->temporaryTemplates[] = $path;

        return $path;
    }

    private function event(): Event
    {
        $client = Client::create([
            'type' => 'active',
            'full_name' => 'María López',
        ]);

        return Event::create([
            'client_id' => $client->id,
            'title' => 'Boda de prueba',
            'event_type' => 'Boda',
            'status' => Event::STATUS_RESERVED,
            'event_date' => '2026-07-09',
            'guest_count' => 150,
            'total_amount' => 100000,
        ]);
    }

    private function contractData(): array
    {
        return [
            'arrendatario_nombre' => 'María López',
            'evento_tipo' => 'Boda',
            'evento_fecha' => '2026-07-09',
            'renta_total' => 100000,
            'fecha_firma' => '2026-07-14',
            'arrendatario_firma_nombre' => 'María López',
        ];
    }
}
