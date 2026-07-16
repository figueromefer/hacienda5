<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Event;
use App\Models\ExpenseConcept;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinancialBalanceCalculator;
use App\Services\FinancialBalanceWorkbook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FinancialBalanceExportTest extends TestCase
{
    use RefreshDatabase;

    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_event_export_is_valid_and_has_correct_totals_references_and_dates(): void
    {
        $user = $this->authorizedUser('manage events');
        $client = $this->client('Diego Chávez');
        $event = $this->event($client, ['total_amount' => 10000]);
        $this->quotation($client, $event, 'approved', 10000);
        $this->quotation($client, $event, 'draft', 50000);

        $this->transaction($client, $event, 'income', 'paid', 3000, 'ING-2026-000001', '2026-07-01');
        $this->transaction($client, $event, 'income', 'paid', 2000, 'ING-2026-000002', '2026-07-05');
        $this->transaction($client, $event, 'income', 'pending', 1000, 'ING-2026-000003', '2026-07-08');
        $this->transaction($client, $event, 'expense', 'paid', 500, 'GAS-2026-000001', '2026-07-09');
        $this->transaction($client, $event, 'income', 'cancelled', 700, 'ING-2026-000004', '2026-07-10');

        [$response, $spreadsheet] = $this->download(
            $this->actingAs($user)->get(route('events.balance.export', $event)),
        );

        $response->assertOk()->assertDownload('balance-evento-'.$event->id.'-diego-chavez.xlsx');
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertSame(['Resumen', 'Movimientos'], $spreadsheet->getSheetNames());

        $summary = $spreadsheet->getSheetByName('Resumen');
        $this->assertSame(10000.0, (float) $summary->getCell('B11')->getCalculatedValue());
        $this->assertSame(5000.0, (float) $summary->getCell('B12')->getCalculatedValue());
        $this->assertSame(5000.0, (float) $summary->getCell('B13')->getCalculatedValue());
        $this->assertSame(500.0, (float) $summary->getCell('B14')->getCalculatedValue());
        $this->assertSame(0.0, (float) $summary->getCell('B15')->getCalculatedValue());
        $this->assertSame(4500.0, (float) $summary->getCell('B16')->getCalculatedValue());
        $this->assertSame('dd/mm/yyyy', $summary->getStyle('B7')->getNumberFormat()->getFormatCode());

        $movements = $spreadsheet->getSheetByName('Movimientos');
        $this->assertSame('ING-2026-000001', $movements->getCell('B6')->getValue());
        $this->assertSame('GAS-2026-000001', $movements->getCell('B9')->getValue());
        $this->assertSame(4500.0, (float) $movements->getCell('J9')->getCalculatedValue());
        $this->assertSame(4500.0, (float) $movements->getCell('J10')->getCalculatedValue());
        $this->assertSame('dd/mm/yyyy', $movements->getStyle('A6')->getNumberFormat()->getFormatCode());
        $this->assertNotNull($movements->getAutoFilter()->getRange());

        $spreadsheet->disconnectWorksheets();
    }

    public function test_client_export_has_event_summaries_and_only_that_clients_movements(): void
    {
        $user = $this->authorizedUser('manage clients');
        $client = $this->client('Diego Chávez');
        $otherClient = $this->client('Cliente Ajeno');
        $firstEvent = $this->event($client, ['title' => 'Boda Diego', 'total_amount' => 12000]);
        $secondEvent = $this->event($client, ['title' => 'Cena Diego', 'total_amount' => 8000]);
        $otherEvent = $this->event($otherClient, ['title' => 'Evento secreto', 'total_amount' => 99999]);
        $this->quotation($client, $firstEvent, 'approved', 12000);
        $this->quotation($client, $secondEvent, 'approved', 8000);
        $this->quotation($otherClient, $otherEvent, 'approved', 99999);

        $this->transaction($client, $firstEvent, 'income', 'paid', 4000, 'ING-2026-000010', '2026-07-01');
        $this->transaction($client, $secondEvent, 'income', 'paid', 3000, 'ING-2026-000011', '2026-07-02');
        $this->transaction($client, null, 'expense', 'paid', 200, 'GAS-2026-000010', '2026-07-03');
        $this->transaction($otherClient, $otherEvent, 'income', 'paid', 99999, 'REFERENCIA-SECRETA', '2026-07-04');

        [$response, $spreadsheet] = $this->download(
            $this->actingAs($user)->get(route('clients.balance.export', $client)),
        );

        $response->assertOk()->assertDownload('balance-cliente-diego-chavez.xlsx');
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertSame([
            'Resumen cliente',
            'Evento '.$firstEvent->id,
            'Evento '.$secondEvent->id,
            'Movimientos',
        ], $spreadsheet->getSheetNames());
        $this->assertSame($spreadsheet->getSheetCount(), count(array_unique($spreadsheet->getSheetNames())));

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $this->assertLessThanOrEqual(31, mb_strlen($sheetName));
            $this->assertFalse(strpbrk($sheetName, '\\/?*[]:'));
        }

        $summary = $spreadsheet->getSheetByName('Resumen cliente');
        $this->assertSame('Boda Diego', $summary->getCell('A6')->getValue());
        $this->assertSame('Cena Diego', $summary->getCell('A7')->getValue());
        $this->assertSame(20000.0, (float) $summary->getCell('D8')->getCalculatedValue());
        $this->assertSame(7000.0, (float) $summary->getCell('E8')->getCalculatedValue());
        $this->assertSame(13000.0, (float) $summary->getCell('F8')->getCalculatedValue());
        $this->assertSame(0.0, (float) $summary->getCell('H8')->getCalculatedValue());
        $this->assertSame(7000.0, (float) $summary->getCell('I8')->getCalculatedValue());

        $movements = $spreadsheet->getSheetByName('Movimientos');
        $values = $movements->rangeToArray('A1:K20');
        $serialized = json_encode($values, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('ING-2026-000010', $serialized);
        $this->assertStringContainsString('GAS-2026-000010', $serialized);
        $this->assertStringNotContainsString('REFERENCIA-SECRETA', $serialized);
        $this->assertStringNotContainsString('Evento secreto', $serialized);

        $spreadsheet->disconnectWorksheets();
    }

    public function test_external_content_is_stored_as_text_and_never_as_a_formula(): void
    {
        $user = $this->authorizedUser('manage events');
        $formula = '=HYPERLINK("https://example.com","test")';
        $client = $this->client('-Cliente accidental');
        $event = $this->event($client, [
            'title' => '@Evento accidental',
            'event_type' => '+Tipo accidental',
        ]);
        $this->transaction($client, $event, 'income', 'paid', 100, $formula, '2026-07-01', '+Concepto accidental');

        [$response, $spreadsheet] = $this->download(
            $this->actingAs($user)->get(route('events.balance.export', $event)),
        );

        $response->assertDownload('balance-evento-'.$event->id.'-cliente-accidental.xlsx');

        $cells = [
            ['Resumen', 'B4', '-Cliente accidental'],
            ['Resumen', 'B5', '@Evento accidental'],
            ['Resumen', 'B6', '+Tipo accidental'],
            ['Movimientos', 'B6', $formula],
            ['Movimientos', 'D6', '+Concepto accidental'],
        ];

        foreach ($cells as [$sheetName, $coordinate, $expected]) {
            $cell = $spreadsheet->getSheetByName($sheetName)->getCell($coordinate);
            $this->assertSame($expected, $cell->getValue());
            $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
            $this->assertFalse($cell->isFormula());
        }

        $spreadsheet->disconnectWorksheets();
    }

    public function test_expense_supplier_and_concept_are_exported_as_safe_text_without_n_plus_one_queries(): void
    {
        $user = $this->authorizedUser('manage events');
        $client = $this->client('Cliente exportación');
        $event = $this->event($client);
        $supplier = Supplier::create(['name' => '=PROVEEDOR()']);
        $concept = ExpenseConcept::create(['name' => '+Concepto']);
        $transaction = $this->transaction($client, $event, 'expense', 'paid', 100, 'GAS-2026-009999', '2026-07-15');
        $transaction->update(['supplier_id' => $supplier->id, 'expense_concept_id' => $concept->id]);

        [, $spreadsheet] = $this->download(
            $this->actingAs($user)->get(route('events.balance.export', $event)),
        );

        foreach ([['E6', '=PROVEEDOR()'], ['F6', '+Concepto']] as [$coordinate, $expected]) {
            $cell = $spreadsheet->getSheetByName('Movimientos')->getCell($coordinate);
            $this->assertSame($expected, $cell->getValue());
            $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
            $this->assertFalse($cell->isFormula());
        }

        $spreadsheet->disconnectWorksheets();
    }

    public function test_financial_calculator_preserves_current_edge_case_behavior(): void
    {
        $event = new Event(['total_amount' => null]);
        $event->setRelation('quotations', collect());
        $event->setRelation('transactions', collect([
            $this->unsavedTransaction('income', 'paid', 100, '2026-07-01'),
            $this->unsavedTransaction('income', 'pending', 30, '2026-07-02'),
            $this->unsavedTransaction('expense', 'paid', 20, '2026-07-03'),
            $this->unsavedTransaction('income', 'cancelled', 50, '2026-07-04'),
            $this->unsavedTransaction('expense', 'paid', -5, '2026-07-05'),
        ]));

        $balance = app(FinancialBalanceCalculator::class)->forEvent($event);

        $this->assertSame('0.00', $balance['approved_quotation_total']);
        $this->assertSame('100.00', $balance['paid_income']);
        $this->assertSame('15.00', $balance['paid_expenses']);
        $this->assertSame('0.00', $balance['pending_receivable']);
        $this->assertSame('100.00', $balance['overpayment']);
        $this->assertSame('85.00', $balance['cash_balance']);
        $this->assertSame(['100.00', '100.00', '80.00', '80.00', '85.00'], $balance['transactions']->pluck('running_balance')->all());
    }

    public function test_client_export_query_count_does_not_grow_with_each_event(): void
    {
        $user = $this->authorizedUser('manage clients');
        $user->getAllPermissions();
        $smallClient = $this->client('Cliente pequeño');
        $smallEvent = $this->event($smallClient);
        $this->transaction($smallClient, $smallEvent, 'income', 'paid', 100, 'ING-2026-100001', '2026-07-01');

        $largeClient = $this->client('Cliente grande');

        foreach (range(1, 8) as $eventNumber) {
            $event = $this->event($largeClient, ['title' => 'Evento '.$eventNumber]);

            foreach (range(1, 4) as $movementNumber) {
                $this->transaction(
                    $largeClient,
                    $event,
                    $movementNumber % 2 === 0 ? 'expense' : 'income',
                    'paid',
                    100,
                    sprintf('QA-%02d-%02d', $eventNumber, $movementNumber),
                    '2026-07-'.str_pad((string) $movementNumber, 2, '0', STR_PAD_LEFT),
                );
            }
        }

        $smallQueries = $this->exportQueryCount($user, $smallClient);
        $largeQueries = $this->exportQueryCount($user, $largeClient);

        $this->assertLessThanOrEqual($smallQueries + 1, $largeQueries);
    }

    public function test_saving_releases_worksheet_memory(): void
    {
        $client = $this->client('Cliente memoria');
        $event = $this->event($client);
        $event->load(['client', 'transactions']);
        $service = app(FinancialBalanceWorkbook::class);
        $spreadsheet = $service->event($event);
        $path = $service->save($spreadsheet);
        $this->temporaryFiles[] = $path;

        $this->assertSame(0, $spreadsheet->getSheetCount());
        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));
    }

    public function test_exports_are_protected_by_existing_permissions(): void
    {
        $client = $this->client('Cliente protegido');
        $event = $this->event($client);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('events.balance.export', $event))->assertForbidden();
        $this->actingAs($user)->get(route('clients.balance.export', $client))->assertForbidden();

        auth()->logout();

        $this->get(route('events.balance.export', $event))->assertRedirect(route('login'));
        $this->get(route('clients.balance.export', $client))->assertRedirect(route('login'));
    }

    public function test_export_buttons_are_available_on_client_and_event_details(): void
    {
        $client = $this->client('Cliente con exportación');
        $event = $this->event($client);
        $eventUser = $this->authorizedUser('manage events');
        $clientUser = $this->authorizedUser('manage clients');

        $this->actingAs($eventUser)->get(route('events.show', $event))
            ->assertOk()
            ->assertSee(route('events.balance.export', $event));

        $this->actingAs($clientUser)->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee(route('clients.balance.export', $client));
    }

    private function download($response): array
    {
        $path = $response->baseResponse->getFile()->getPathname();
        $this->temporaryFiles[] = $path;

        return [$response, IOFactory::load($path)];
    }

    private function exportQueryCount(User $user, Client $client): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('clients.balance.export', $client));
        $response->assertOk();
        $this->temporaryFiles[] = $response->baseResponse->getFile()->getPathname();

        $queries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_starts_with(strtolower(ltrim($query['query'])), 'select'))
            ->count();

        DB::disableQueryLog();

        return $queries;
    }

    private function authorizedUser(string $permission): User
    {
        $user = User::factory()->create();
        $permissionModel = Permission::findOrCreate($permission);
        $user->givePermissionTo($permissionModel);

        return $user;
    }

    private function client(string $name): Client
    {
        return Client::create([
            'type' => 'active',
            'full_name' => $name,
        ]);
    }

    private function event(Client $client, array $attributes = []): Event
    {
        return Event::create(array_merge([
            'client_id' => $client->id,
            'title' => 'Evento de prueba',
            'event_type' => 'Social',
            'status' => Event::STATUS_CONFIRMED,
            'event_date' => '2026-09-20',
            'total_amount' => 10000,
        ], $attributes));
    }

    private function transaction(
        Client $client,
        ?Event $event,
        string $type,
        string $status,
        float $amount,
        string $reference,
        string $date,
        string $category = 'Movimiento de prueba',
    ): Transaction {
        return Transaction::create([
            'client_id' => $client->id,
            'event_id' => $event?->id,
            'type' => $type,
            'scope' => $event ? 'event' : 'operation',
            'transaction_date' => $date,
            'amount' => $amount,
            'method' => 'transfer',
            'category' => $category,
            'reference' => $reference,
            'status' => $status,
        ]);
    }

    private function quotation(Client $client, Event $event, string $status, float $total): Quotation
    {
        return Quotation::create([
            'client_id' => $client->id,
            'event_id' => $event->id,
            'folio' => 'COT-'.fake()->unique()->numerify('######'),
            'status' => $status,
            'subtotal' => $total,
            'discount' => 0,
            'total' => $total,
        ]);
    }

    private function unsavedTransaction(string $type, string $status, float $amount, string $date): Transaction
    {
        return new Transaction([
            'type' => $type,
            'scope' => 'event',
            'transaction_date' => $date,
            'amount' => $amount,
            'status' => $status,
        ]);
    }
}
