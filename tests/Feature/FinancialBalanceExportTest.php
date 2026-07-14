<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Event;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->transaction($client, $event, 'income', 'paid', 3000, 'ING-2026-000001', '2026-07-01');
        $this->transaction($client, $event, 'income', 'paid', 2000, 'ING-2026-000002', '2026-07-05');
        $this->transaction($client, $event, 'income', 'pending', 1000, 'ING-2026-000003', '2026-07-08');
        $this->transaction($client, $event, 'expense', 'paid', 500, 'GAS-2026-000001', '2026-07-09');
        $this->transaction($client, $event, 'income', 'cancelled', 700, 'ING-2026-000004', '2026-07-10');

        [$response, $spreadsheet] = $this->download(
            $this->actingAs($user)->get(route('events.balance.export', $event)),
        );

        $response->assertOk()->assertDownload('balance-evento-'.$event->id.'-diego-chavez.xlsx');
        $this->assertSame(['Resumen', 'Movimientos'], $spreadsheet->getSheetNames());

        $summary = $spreadsheet->getSheetByName('Resumen');
        $this->assertSame(10000.0, (float) $summary->getCell('B11')->getCalculatedValue());
        $this->assertSame(5000.0, (float) $summary->getCell('B12')->getCalculatedValue());
        $this->assertSame(1000.0, (float) $summary->getCell('B13')->getCalculatedValue());
        $this->assertSame(500.0, (float) $summary->getCell('B14')->getCalculatedValue());
        $this->assertSame(5000.0, (float) $summary->getCell('B15')->getCalculatedValue());
        $this->assertSame(4500.0, (float) $summary->getCell('B16')->getCalculatedValue());
        $this->assertSame('dd/mm/yyyy', $summary->getStyle('B7')->getNumberFormat()->getFormatCode());

        $movements = $spreadsheet->getSheetByName('Movimientos');
        $this->assertSame('ING-2026-000001', $movements->getCell('B6')->getValue());
        $this->assertSame('GAS-2026-000001', $movements->getCell('B9')->getValue());
        $this->assertSame(4500.0, (float) $movements->getCell('H9')->getCalculatedValue());
        $this->assertSame(4500.0, (float) $movements->getCell('H10')->getCalculatedValue());
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

        $this->transaction($client, $firstEvent, 'income', 'paid', 4000, 'ING-2026-000010', '2026-07-01');
        $this->transaction($client, $secondEvent, 'income', 'paid', 3000, 'ING-2026-000011', '2026-07-02');
        $this->transaction($client, null, 'expense', 'paid', 200, 'GAS-2026-000010', '2026-07-03');
        $this->transaction($otherClient, $otherEvent, 'income', 'paid', 99999, 'REFERENCIA-SECRETA', '2026-07-04');

        [$response, $spreadsheet] = $this->download(
            $this->actingAs($user)->get(route('clients.balance.export', $client)),
        );

        $response->assertOk()->assertDownload('balance-cliente-diego-chavez.xlsx');
        $this->assertSame([
            'Resumen cliente',
            'Evento '.$firstEvent->id,
            'Evento '.$secondEvent->id,
            'Movimientos',
        ], $spreadsheet->getSheetNames());

        $summary = $spreadsheet->getSheetByName('Resumen cliente');
        $this->assertSame('Boda Diego', $summary->getCell('A6')->getValue());
        $this->assertSame('Cena Diego', $summary->getCell('A7')->getValue());
        $this->assertSame(20000.0, (float) $summary->getCell('D8')->getCalculatedValue());
        $this->assertSame(7000.0, (float) $summary->getCell('E8')->getCalculatedValue());
        $this->assertSame(13000.0, (float) $summary->getCell('H8')->getCalculatedValue());
        $this->assertSame(7000.0, (float) $summary->getCell('I8')->getCalculatedValue());

        $movements = $spreadsheet->getSheetByName('Movimientos');
        $values = $movements->rangeToArray('A1:I20');
        $serialized = json_encode($values, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('ING-2026-000010', $serialized);
        $this->assertStringContainsString('GAS-2026-000010', $serialized);
        $this->assertStringNotContainsString('REFERENCIA-SECRETA', $serialized);
        $this->assertStringNotContainsString('Evento secreto', $serialized);

        $spreadsheet->disconnectWorksheets();
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
    ): Transaction {
        return Transaction::create([
            'client_id' => $client->id,
            'event_id' => $event?->id,
            'type' => $type,
            'scope' => $event ? 'event' : 'operation',
            'transaction_date' => $date,
            'amount' => $amount,
            'method' => 'transfer',
            'category' => 'Movimiento de prueba',
            'reference' => $reference,
            'status' => $status,
        ]);
    }
}
