<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Event;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\SupplierPayable;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionReferenceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TransactionPhaseFiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_income_and_expense_pages_share_paid_totals_without_cancelled_or_pending_history(): void
    {
        $income = $this->transaction(['type' => Transaction::TYPE_INCOME, 'amount' => 500, 'reference' => 'ING-VIGENTE']);
        $this->transaction(['type' => Transaction::TYPE_INCOME, 'amount' => 200, 'status' => Transaction::STATUS_PENDING, 'reference' => 'ING-PENDIENTE']);
        $this->transaction(['type' => Transaction::TYPE_INCOME, 'amount' => 900, 'status' => Transaction::STATUS_CANCELLED, 'reference' => 'ING-CANCELADO']);
        $expense = $this->transaction(['type' => Transaction::TYPE_EXPENSE, 'amount' => 300, 'reference' => 'GAS-VIGENTE']);
        $this->transaction(['type' => Transaction::TYPE_EXPENSE, 'amount' => 100, 'status' => Transaction::STATUS_PENDING, 'reference' => 'GAS-PENDIENTE']);
        $this->transaction(['type' => Transaction::TYPE_EXPENSE, 'amount' => 700, 'status' => Transaction::STATUS_CANCELLED, 'reference' => 'GAS-CANCELADO']);

        $incomeResponse = $this->actingAs($this->user())->get(route('incomes.index'));
        $incomeResponse->assertOk()
            ->assertViewIs('transactions.type-index')
            ->assertViewHas('total', fn ($total) => (float) $total === 500.0)
            ->assertViewHas('pendingTotal', fn ($total) => (float) $total === 200.0)
            ->assertSee($income->reference)
            ->assertDontSee($expense->reference);

        $expenseResponse = $this->actingAs($this->user())->get(route('expenses.index'));
        $expenseResponse->assertOk()
            ->assertViewIs('transactions.type-index')
            ->assertViewHas('total', fn ($total) => (float) $total === 300.0)
            ->assertViewHas('pendingTotal', fn ($total) => (float) $total === 100.0)
            ->assertSee($expense->reference)
            ->assertDontSee($income->reference);
    }

    public function test_create_from_event_preselects_type_client_event_and_cancel_context(): void
    {
        $client = $this->client();
        $event = $this->event($client);

        $this->actingAs($this->user())->get(route('transactions.create', [
            'type' => Transaction::TYPE_EXPENSE,
            'event_id' => $event->id,
        ]))->assertOk()
            ->assertSee($event->title)
            ->assertSee($client->full_name)
            ->assertSee('expense')
            ->assertSee('events');
    }

    public function test_new_movement_normalizes_amount_forces_paid_and_ignores_removed_fields(): void
    {
        Mail::fake();
        $client = $this->client();

        $this->actingAs($this->user())->post(route('transactions.store'), $this->payload($client, [
            'amount' => '$ 12,345.67',
            'status' => Transaction::STATUS_CANCELLED,
            'category' => 'No debe persistir',
        ]))->assertRedirect(route('transactions.index'));

        $transaction = Transaction::firstOrFail();
        $this->assertSame('12345.67', $transaction->amount);
        $this->assertSame(Transaction::STATUS_PAID, $transaction->status);
        $this->assertNull($transaction->category);
    }

    public function test_server_rejects_incoherent_client_event_and_quotation_combinations(): void
    {
        $client = $this->client(['full_name' => 'Cliente correcto']);
        $otherClient = $this->client(['full_name' => 'Cliente ajeno']);
        $event = $this->event($client);
        $otherEvent = $this->event($otherClient, ['title' => 'Evento ajeno']);
        $quotation = Quotation::create([
            'client_id' => $client->id,
            'event_id' => $event->id,
            'status' => 'approved',
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
        ]);

        $this->actingAs($this->user())->post(route('transactions.store'), $this->payload($client, [
            'scope' => 'event',
            'event_id' => $otherEvent->id,
        ]))->assertSessionHasErrors('event_id');

        $this->actingAs($this->user())->post(route('transactions.store'), $this->payload($client, [
            'scope' => 'event',
            'event_id' => $event->id,
            'quotation_id' => $quotation->id,
            'client_id' => $otherClient->id,
        ]))->assertSessionHasErrors(['event_id', 'quotation_id']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_cancellation_is_audited_idempotent_and_reopens_related_payable(): void
    {
        $user = $this->user();
        $supplier = Supplier::create(['name' => 'Proveedor auditado']);
        $payable = SupplierPayable::create([
            'supplier_id' => $supplier->id,
            'description' => 'Cuenta de prueba',
            'total_amount' => 500,
            'status' => SupplierPayable::STATUS_PENDING,
        ]);
        $transaction = $this->transaction([
            'client_id' => null,
            'type' => Transaction::TYPE_EXPENSE,
            'supplier_id' => $supplier->id,
            'supplier_payable_id' => $payable->id,
            'amount' => 500,
        ]);
        $this->assertSame(SupplierPayable::STATUS_PAID, $payable->refresh()->status);

        $this->actingAs($user)->patch(route('transactions.cancel', $transaction))->assertSessionHas('success');

        $transaction->refresh();
        $this->assertSame(Transaction::STATUS_CANCELLED, $transaction->status);
        $this->assertNotNull($transaction->cancelled_at);
        $this->assertSame($user->id, $transaction->cancelled_by);
        $this->assertSame(SupplierPayable::STATUS_PENDING, $payable->refresh()->status);
        $cancelledAt = $transaction->cancelled_at->toISOString();
        $this->assertStringContainsString('DOCUMENTO CANCELADO', view('transactions.receipt-pdf', [
            'transaction' => $transaction,
            'receiptTitle' => 'RECIBO PAGO TRABAJOS',
            'amountInWords' => 'QUINIENTOS PESOS 00/100 M.N.',
            'logoPath' => null,
            'publicUrl' => null,
        ])->render());

        $this->actingAs($user)->patch(route('transactions.cancel', $transaction))->assertSessionHas('warning');
        $this->assertSame($cancelledAt, $transaction->refresh()->cancelled_at->toISOString());
        $this->assertSame($user->id, $transaction->cancelled_by);
    }

    public function test_cancelled_movement_cannot_be_edited_and_has_no_delete_route(): void
    {
        $transaction = $this->transaction(['status' => Transaction::STATUS_CANCELLED]);

        $this->actingAs($this->user())->get(route('transactions.edit', $transaction))->assertStatus(422);
        $this->actingAs($this->user())->put(route('transactions.update', $transaction), $this->payload($transaction->client))->assertStatus(422);
        $this->actingAs($this->user())->delete('/transactions/'.$transaction->id)->assertMethodNotAllowed();
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_pending_history_keeps_its_status_when_safe_fields_are_edited(): void
    {
        $transaction = $this->transaction(['status' => Transaction::STATUS_PENDING]);

        $this->actingAs($this->user())->put(route('transactions.update', $transaction), $this->payload($transaction->client, [
            'notes' => 'Dato histórico corregido',
            'status' => Transaction::STATUS_PAID,
        ]))->assertRedirect(route('transactions.index'));

        $transaction->refresh();
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->status);
        $this->assertSame('Dato histórico corregido', $transaction->notes);
    }

    public function test_proof_is_private_authorized_replaceable_and_preserved_on_cancellation(): void
    {
        Storage::fake('local');
        Mail::fake();
        $user = $this->user();
        $client = $this->client();

        $this->actingAs($user)->post(route('transactions.store'), $this->payload($client, [
            'proof_file' => UploadedFile::fake()->create('comprobante.pdf', 120, 'application/pdf'),
        ]))->assertRedirect(route('transactions.index'));

        $transaction = Transaction::firstOrFail();
        Storage::disk('local')->assertExists($transaction->proof_file_path);
        $this->assertSame('comprobante.pdf', $transaction->proof_original_name);
        $this->assertSame('application/pdf', $transaction->proof_mime_type);
        $oldPath = $transaction->proof_file_path;

        auth()->logout();
        $this->get(route('transactions.proof', $transaction))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('transactions.proof', $transaction))->assertForbidden();
        $this->actingAs($user)->get(route('transactions.proof', $transaction))->assertOk();

        $this->actingAs($user)->put(route('transactions.update', $transaction), $this->payload($client, [
            'proof_file' => UploadedFile::fake()->image('nuevo.png'),
        ]))->assertRedirect(route('transactions.index'));

        $transaction->refresh();
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($transaction->proof_file_path);
        $this->assertSame('nuevo.png', $transaction->proof_original_name);

        $proofPath = $transaction->proof_file_path;
        $this->actingAs($user)->patch(route('transactions.cancel', $transaction));
        Storage::disk('local')->assertExists($proofPath);
        $this->assertSame($proofPath, $transaction->refresh()->proof_file_path);
    }

    public function test_failed_database_transaction_removes_newly_stored_proof(): void
    {
        Storage::fake('local');
        $generator = Mockery::mock(TransactionReferenceGenerator::class);
        $generator->shouldReceive('next')->once()->andThrow(new RuntimeException('Fallo simulado de referencia'));
        $this->app->instance(TransactionReferenceGenerator::class, $generator);
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->user())->post(route('transactions.store'), $this->payload($this->client(), [
                'proof_file' => UploadedFile::fake()->create('rollback.pdf', 50, 'application/pdf'),
            ]));
            $this->fail('La excepción simulada no fue lanzada.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fallo simulado de referencia', $exception->getMessage());
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('transactions', 0);
    }

    private function payload(Client $client, array $attributes = []): array
    {
        return array_merge([
            'client_id' => $client->id,
            'type' => Transaction::TYPE_INCOME,
            'scope' => 'operation',
            'transaction_date' => '2026-07-16',
            'amount' => '500.00',
            'method' => 'transfer',
            'notes' => 'Movimiento de prueba',
        ], $attributes);
    }

    private function transaction(array $attributes = []): Transaction
    {
        return Transaction::create(array_merge([
            'client_id' => $this->client()->id,
            'type' => Transaction::TYPE_INCOME,
            'scope' => 'operation',
            'transaction_date' => '2026-07-16',
            'amount' => 100,
            'status' => Transaction::STATUS_PAID,
        ], $attributes));
    }

    private function client(array $attributes = []): Client
    {
        return Client::create(array_merge([
            'type' => 'active',
            'full_name' => 'Cliente fase cinco '.uniqid(),
        ], $attributes));
    }

    private function event(Client $client, array $attributes = []): Event
    {
        return Event::create(array_merge([
            'client_id' => $client->id,
            'title' => 'Evento fase cinco',
            'event_type' => 'Boda',
            'status' => Event::STATUS_CONFIRMED,
            'event_date' => '2026-09-20',
        ], $attributes));
    }

    private function user(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('manage payments'));

        return $user;
    }
}
