<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Event;
use App\Models\ExpenseConcept;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TransactionPhaseEightTest extends TestCase
{
    use RefreshDatabase;

    public function test_movement_search_covers_visible_fields_relationships_and_spanish_labels(): void
    {
        $user = $this->userWithPermissions(['manage payments']);
        $client = Client::create(['type' => 'active', 'full_name' => 'Cliente Luciérnaga']);
        $event = $this->event($client, 'Cena Jardín');
        $quotation = Quotation::create([
            'client_id' => $client->id,
            'event_id' => $event->id,
            'folio' => 'C-008765',
            'status' => 'approved',
            'subtotal' => 765.43,
            'discount' => 0,
            'total' => 765.43,
        ]);
        $supplier = Supplier::create(['name' => 'Proveedor Magnolia']);
        $concept = ExpenseConcept::create(['name' => 'Iluminación arquitectónica']);
        $matching = Transaction::create([
            'client_id' => $client->id,
            'event_id' => $event->id,
            'quotation_id' => $quotation->id,
            'supplier_id' => $supplier->id,
            'expense_concept_id' => $concept->id,
            'type' => Transaction::TYPE_EXPENSE,
            'scope' => 'event',
            'transaction_date' => '2026-07-19',
            'amount' => 765.43,
            'method' => 'transfer',
            'reference' => 'GAS-BUSQUEDA-8765',
            'status' => Transaction::STATUS_PAID,
            'notes' => 'Montaje nocturno especial',
        ]);
        Transaction::create([
            'client_id' => Client::create(['type' => 'active', 'full_name' => 'Cliente ajeno'])->id,
            'type' => Transaction::TYPE_INCOME,
            'scope' => 'operation',
            'transaction_date' => '2026-01-01',
            'amount' => 10,
            'method' => 'cash',
            'reference' => 'NO-MOSTRAR',
            'status' => Transaction::STATUS_PENDING,
            'notes' => 'Dato irrelevante',
        ]);

        foreach ([
            'GAS-BUSQUEDA',
            'Luciérnaga',
            'Cena Jardín',
            'C-008765',
            'Magnolia',
            'Iluminación',
            'Gasto',
            'Pagado',
            'Transferencia',
            '765.43',
            '2026-07-19',
            'nocturno especial',
        ] as $search) {
            $this->actingAs($user)->get(route('transactions.index', ['search' => $search]))
                ->assertOk()
                ->assertSee($matching->reference)
                ->assertDontSee('NO-MOSTRAR');
        }
    }

    public function test_movement_table_uses_dropdown_badges_and_preserves_search_on_pagination(): void
    {
        $user = $this->userWithPermissions(['manage payments']);
        $client = Client::create(['type' => 'active', 'full_name' => 'Cliente paginado']);

        foreach (range(1, 16) as $number) {
            Transaction::create([
                'client_id' => $client->id,
                'type' => Transaction::TYPE_INCOME,
                'scope' => 'operation',
                'transaction_date' => '2026-07-19',
                'amount' => $number,
                'method' => 'cash',
                'reference' => 'PAGINADO-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'status' => Transaction::STATUS_PAID,
                'notes' => 'Grupo paginado',
            ]);
        }

        $response = $this->actingAs($user)->get(route('transactions.index', ['search' => 'Grupo paginado']));

        $response->assertOk()
            ->assertSee('Acciones')
            ->assertSee('Pagado')
            ->assertSee('search=Grupo%20paginado', false);
    }

    public function test_receipt_return_context_only_accepts_known_origins(): void
    {
        $user = $this->userWithPermissions(['manage payments']);
        $client = Client::create(['type' => 'active', 'full_name' => 'Cliente contexto']);
        $event = $this->event($client, 'Evento contexto');
        $expense = $this->transaction($client, $event, Transaction::TYPE_EXPENSE);
        $income = $this->transaction($client, $event, Transaction::TYPE_INCOME);

        $this->actingAs($user)->get(route('transactions.show', ['transaction' => $expense, 'origin' => 'expenses']))
            ->assertOk()->assertSee(route('expenses.index'), false)->assertSee('Volver a Gastos');
        $this->actingAs($user)->get(route('transactions.show', ['transaction' => $income, 'origin' => 'incomes']))
            ->assertOk()->assertSee(route('incomes.index'), false)->assertSee('Volver a Ingresos');
        $this->actingAs($user)->get(route('transactions.show', ['transaction' => $income, 'origin' => 'event']))
            ->assertOk()->assertSee(route('events.show', $event), false)->assertSee('Volver al evento');
        $this->actingAs($user)->get(route('transactions.show', ['transaction' => $income, 'origin' => 'https://attacker.test']))
            ->assertOk()->assertSee(route('transactions.index'), false)->assertDontSee('attacker.test');
    }

    public function test_event_receipt_opens_safely_in_new_tab_and_pdf_contains_authenticity_and_spanish_status(): void
    {
        $user = $this->userWithPermissions(['manage payments', 'manage events']);
        $client = Client::create(['type' => 'active', 'full_name' => 'Cliente recibo']);
        $event = $this->event($client, 'Evento recibo');
        $transaction = $this->transaction($client, $event, Transaction::TYPE_EXPENSE, [
            'receipt_token' => '11111111-1111-4111-8111-111111111111',
            'status' => Transaction::STATUS_PENDING,
            'notes' => 'Trabajo representativo para recibo',
        ]);

        $eventHtml = $this->actingAs($user)->get(route('events.show', $event))->assertOk()->getContent();
        $receiptUrl = route('transactions.show', ['transaction' => $transaction, 'origin' => 'event']);
        $this->assertStringContainsString('href="'.$receiptUrl.'"', $eventHtml);
        $this->assertStringContainsString('target="_blank"', $eventHtml);
        $this->assertStringContainsString('rel="noopener noreferrer"', $eventHtml);

        $html = view('transactions.receipt-pdf', [
            'transaction' => $transaction->load(['client', 'event']),
            'receiptTitle' => 'RECIBO PAGO TRABAJOS',
            'amountInWords' => 'CIEN PESOS 00/100 M.N.',
            'logoPath' => null,
            'publicUrl' => route('receipts.public.show', $transaction->receipt_token),
        ])->render();

        $this->assertStringContainsString('Verificacion de autenticidad', $html);
        $this->assertStringContainsString('Pendiente histórico', $html);
        $this->assertStringContainsString('@page{margin:0}', $html);
    }

    private function transaction(Client $client, Event $event, string $type, array $attributes = []): Transaction
    {
        return Transaction::create(array_merge([
            'client_id' => $client->id,
            'event_id' => $event->id,
            'type' => $type,
            'scope' => 'event',
            'transaction_date' => '2026-07-20',
            'amount' => 100,
            'method' => 'cash',
            'reference' => uniqid('MOV-'),
            'status' => Transaction::STATUS_PAID,
        ], $attributes));
    }

    private function event(Client $client, string $title): Event
    {
        return Event::create([
            'client_id' => $client->id,
            'title' => $title,
            'event_type' => 'Boda',
            'status' => Event::STATUS_CONFIRMED,
            'event_date' => '2026-08-20',
        ]);
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo($permissions);

        return $user;
    }
}
