<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ExpenseConcept;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TransactionExpenseAssociationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_can_be_created_without_associations(): void
    {
        $this->actingAs($this->user())->post(route('transactions.store'), $this->payload())
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'type' => 'expense',
            'supplier_id' => null,
            'expense_concept_id' => null,
        ]);
    }

    public function test_expense_can_add_change_and_remove_associations(): void
    {
        $supplier = Supplier::create(['name' => 'Flores']);
        $concept = ExpenseConcept::create(['name' => 'Decoración']);
        $user = $this->user();

        $this->actingAs($user)->post(route('transactions.store'), $this->payload([
            'supplier_id' => $supplier->id,
            'expense_concept_id' => $concept->id,
        ]))->assertRedirect(route('transactions.index'));

        $transaction = Transaction::latest('id')->firstOrFail();
        $this->assertTrue($transaction->supplier->is($supplier));
        $this->assertTrue($transaction->expenseConcept->is($concept));

        $this->actingAs($user)->put(route('transactions.update', $transaction), $this->payload([
            'supplier_id' => null,
            'expense_concept_id' => null,
        ]))->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'supplier_id' => null, 'expense_concept_id' => null]);
    }

    public function test_nonexistent_association_ids_are_rejected(): void
    {
        $this->actingAs($this->user())->post(route('transactions.store'), $this->payload([
            'supplier_id' => 999999,
            'expense_concept_id' => 999999,
        ]))->assertSessionHasErrors(['supplier_id', 'expense_concept_id']);
    }

    public function test_income_forced_with_association_ids_saves_null(): void
    {
        $supplier = Supplier::create(['name' => 'Proveedor']);
        $concept = ExpenseConcept::create(['name' => 'Concepto']);

        $this->actingAs($this->user())->post(route('transactions.store'), $this->payload([
            'type' => 'income',
            'supplier_id' => 999998,
            'expense_concept_id' => 999999,
        ]))->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', ['type' => 'income', 'supplier_id' => null, 'expense_concept_id' => null]);
    }

    public function test_edit_shows_only_active_options_and_current_archived_associations(): void
    {
        $currentSupplier = Supplier::create(['name' => 'Proveedor actual', 'is_active' => false]);
        Supplier::create(['name' => 'Proveedor archivado ajeno', 'is_active' => false]);
        $activeSupplier = Supplier::create(['name' => 'Proveedor activo']);
        $currentConcept = ExpenseConcept::create(['name' => 'Concepto actual', 'is_active' => false]);
        ExpenseConcept::create(['name' => 'Concepto archivado ajeno', 'is_active' => false]);
        $activeConcept = ExpenseConcept::create(['name' => 'Concepto activo']);
        $transaction = $this->transaction($currentSupplier, $currentConcept);

        $this->actingAs($this->user())->get(route('transactions.edit', $transaction))
            ->assertOk()
            ->assertSee('Proveedor actual (archivado)')
            ->assertSee('Concepto actual (archivado)')
            ->assertSee($activeSupplier->name)
            ->assertSee($activeConcept->name)
            ->assertDontSee('Proveedor archivado ajeno')
            ->assertDontSee('Concepto archivado ajeno');
    }

    public function test_deleting_catalog_records_preserves_historical_transaction(): void
    {
        $supplier = Supplier::create(['name' => 'Proveedor histórico']);
        $concept = ExpenseConcept::create(['name' => 'Concepto histórico']);
        $transaction = $this->transaction($supplier, $concept);

        $supplier->delete();
        $concept->delete();

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'supplier_id' => null, 'expense_concept_id' => null]);
    }

    private function payload(array $attributes = []): array
    {
        return array_merge([
            'client_id' => $this->client()->id,
            'type' => 'expense',
            'scope' => 'operation',
            'transaction_date' => '2026-07-15',
            'amount' => 500,
            'method' => 'transfer',
            'status' => 'paid',
        ], $attributes);
    }

    private function transaction(Supplier $supplier, ExpenseConcept $concept): Transaction
    {
        return Transaction::create($this->payload([
            'supplier_id' => $supplier->id,
            'expense_concept_id' => $concept->id,
        ]));
    }

    private function client(): Client
    {
        return Client::firstOrCreate(['full_name' => 'Cliente de gastos'], ['type' => 'active']);
    }

    private function user(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('manage payments'));

        return $user;
    }
}
