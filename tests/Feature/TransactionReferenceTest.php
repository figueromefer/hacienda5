<?php

namespace Tests\Feature;

use App\Mail\IncomeReceiptMail;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionReferenceGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use LogicException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TransactionReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_income_and_expense_use_independent_sequences(): void
    {
        $income = $this->createThroughController(Transaction::TYPE_INCOME, '2026-07-14');
        $expense = $this->createThroughController(Transaction::TYPE_EXPENSE, '2026-07-14');

        $this->assertSame('ING-2026-000001', $income->reference);
        $this->assertSame('GAS-2026-000001', $expense->reference);
    }

    public function test_sequence_restarts_for_a_new_year(): void
    {
        $first = $this->createThroughController(Transaction::TYPE_INCOME, '2026-12-31');
        $nextYear = $this->createThroughController(Transaction::TYPE_INCOME, '2027-01-01');

        $this->assertSame('ING-2026-000001', $first->reference);
        $this->assertSame('ING-2027-000001', $nextYear->reference);
    }

    public function test_manual_reference_is_preserved_and_does_not_consume_sequence(): void
    {
        $manual = $this->createThroughController(Transaction::TYPE_INCOME, '2026-07-14', 'TRANSFERENCIA-BANCO-88');
        $automatic = $this->createThroughController(Transaction::TYPE_INCOME, '2026-07-15');

        $this->assertSame('TRANSFERENCIA-BANCO-88', $manual->reference);
        $this->assertSame('ING-2026-000001', $automatic->reference);
    }

    public function test_existing_formatted_references_are_skipped_when_sequence_starts(): void
    {
        $this->transaction([
            'reference' => 'ING-2026-000025',
            'transaction_date' => '2026-01-01',
        ]);

        $automatic = $this->createThroughController(Transaction::TYPE_INCOME, '2026-07-15');

        $this->assertSame('ING-2026-000026', $automatic->reference);
    }

    public function test_sequence_allocation_is_serialized_and_references_remain_unique(): void
    {
        $generator = app(TransactionReferenceGenerator::class);

        $references = collect(range(1, 5))->map(fn () => DB::transaction(
            fn () => $generator->next(Transaction::TYPE_INCOME, '2026-07-14'),
        ));

        $this->assertSame([
            'ING-2026-000001',
            'ING-2026-000002',
            'ING-2026-000003',
            'ING-2026-000004',
            'ING-2026-000005',
        ], $references->all());
        $this->assertDatabaseCount('transaction_sequences', 1);
        $this->assertDatabaseHas('transaction_sequences', [
            'year' => 2026,
            'type' => Transaction::TYPE_INCOME,
            'last_number' => 5,
        ]);
    }

    public function test_database_unique_index_rejects_duplicate_references(): void
    {
        $this->transaction(['reference' => 'ING-2026-000001']);

        $this->expectException(QueryException::class);
        $this->transaction(['reference' => 'ING-2026-000001']);
    }

    public function test_reference_is_immutable_after_creation(): void
    {
        $transaction = $this->transaction(['reference' => 'ING-2026-000001']);

        $this->expectException(LogicException::class);
        $transaction->update(['reference' => 'ING-2026-000002']);
    }

    public function test_backfill_dry_run_does_not_change_transactions_or_sequences(): void
    {
        $transaction = $this->transaction(['reference' => null]);

        $this->artisan('transactions:backfill-references', ['--dry-run' => true])
            ->expectsOutputToContain('#'.$transaction->id.' -> ING-2026-000001')
            ->expectsOutputToContain('No se realizaron cambios.')
            ->assertSuccessful();

        $this->assertNull($transaction->fresh()->reference);
        $this->assertDatabaseCount('transaction_sequences', 0);
    }

    public function test_backfill_only_updates_missing_references(): void
    {
        $missing = $this->transaction(['reference' => null]);
        $manual = $this->transaction(['reference' => 'REFERENCIA-HISTORICA']);

        $this->artisan('transactions:backfill-references')->assertSuccessful();

        $this->assertSame('ING-2026-000001', $missing->fresh()->reference);
        $this->assertSame('REFERENCIA-HISTORICA', $manual->fresh()->reference);
    }

    public function test_reference_is_visible_in_receipt_surfaces(): void
    {
        $transaction = $this->transaction([
            'reference' => 'ING-2026-000123',
            'receipt_token' => '11111111-1111-4111-8111-111111111111',
        ]);
        $user = $this->userWithPaymentPermission();

        $this->actingAs($user)->get(route('transactions.index'))->assertOk()->assertSee($transaction->reference);
        $this->actingAs($user)->get(route('transactions.show', $transaction))->assertOk()->assertSee($transaction->reference);
        $this->actingAs($user)->get(route('transactions.edit', $transaction))
            ->assertOk()
            ->assertSee($transaction->reference)
            ->assertDontSee('name="reference"', false);
        $this->get(route('receipts.public.show', $transaction->receipt_token))->assertOk()->assertSee($transaction->reference);

        $this->assertStringContainsString($transaction->reference, (new IncomeReceiptMail($transaction))->render());
        $this->assertStringContainsString(
            $transaction->reference,
            view('transactions.receipt-pdf', $this->receiptViewData($transaction))->render(),
        );
    }

    private function createThroughController(string $type, string $date, ?string $reference = null): Transaction
    {
        Mail::fake();
        $client = $this->client();
        $user = $this->userWithPaymentPermission();

        $this->actingAs($user)->post(route('transactions.store'), [
            'client_id' => $client->id,
            'type' => $type,
            'scope' => 'operation',
            'transaction_date' => $date,
            'amount' => 100,
            'method' => 'transfer',
            'category' => 'Prueba',
            'reference' => $reference,
            'status' => 'paid',
        ])->assertRedirect(route('transactions.index'));

        return Transaction::query()->latest('id')->firstOrFail();
    }

    private function transaction(array $attributes = []): Transaction
    {
        return Transaction::create(array_merge([
            'client_id' => $this->client()->id,
            'type' => Transaction::TYPE_INCOME,
            'scope' => 'operation',
            'transaction_date' => '2026-07-14',
            'amount' => 100,
            'status' => 'paid',
        ], $attributes));
    }

    private function client(): Client
    {
        return Client::create([
            'type' => 'active',
            'full_name' => 'Cliente de referencias',
        ]);
    }

    private function userWithPaymentPermission(): User
    {
        $user = User::factory()->create();
        $permission = Permission::findOrCreate('manage payments');
        $user->givePermissionTo($permission);

        return $user;
    }

    private function receiptViewData(Transaction $transaction): array
    {
        return [
            'transaction' => $transaction->load(['client', 'event', 'quotation']),
            'receiptTitle' => 'RECIBO DE ANTICIPO',
            'amountInWords' => 'CIEN PESOS 00/100 M.N.',
            'logoPath' => public_path('images/hacienda-cinco-logo.png'),
            'brandGreen' => '#243834',
            'publicUrl' => route('receipts.public.show', $transaction->receipt_token),
        ];
    }
}
