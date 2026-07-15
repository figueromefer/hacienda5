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

class ExpenseModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_requires_the_existing_payments_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('expenses.index'))
            ->assertForbidden();
    }

    public function test_expenses_lists_only_expenses_and_preselects_expense_for_creation(): void
    {
        $expense = $this->transaction(['type' => 'expense', 'reference' => 'GAS-LISTADO']);
        $income = $this->transaction(['type' => 'income', 'reference' => 'ING-NO-MOSTRAR']);

        $response = $this->actingAs($this->user())->get(route('expenses.index'));

        $response->assertOk()
            ->assertSee($expense->reference)
            ->assertDontSee($income->reference)
            ->assertSee(route('transactions.create', ['type' => 'expense']), false)
            ->assertSee(route('transactions.show', $expense), false)
            ->assertSee(route('transactions.edit', $expense), false);
    }

    public function test_filters_and_totals_apply_to_the_same_expense_query(): void
    {
        $supplier = Supplier::create(['name' => 'Proveedor filtrado']);
        $otherSupplier = Supplier::create(['name' => 'Proveedor ajeno']);
        $concept = ExpenseConcept::create(['name' => 'Decoración especial']);

        $matching = $this->transaction([
            'supplier_id' => $supplier->id,
            'expense_concept_id' => $concept->id,
            'transaction_date' => '2026-07-10',
            'status' => 'paid',
            'amount' => 300,
            'notes' => 'Carpa jardín',
            'reference' => 'GAS-FILTRADO',
        ]);
        $this->transaction([
            'supplier_id' => $otherSupplier->id,
            'expense_concept_id' => $concept->id,
            'transaction_date' => '2026-07-10',
            'status' => 'paid',
            'amount' => 900,
            'notes' => 'Carpa jardín',
            'reference' => 'GAS-AJENO',
        ]);
        $this->transaction([
            'supplier_id' => $supplier->id,
            'expense_concept_id' => $concept->id,
            'transaction_date' => '2026-07-10',
            'status' => 'pending',
            'amount' => 500,
            'notes' => 'Carpa jardín',
            'reference' => 'GAS-PENDIENTE',
        ]);

        $response = $this->actingAs($this->user())->get(route('expenses.index', [
            'search' => 'Carpa',
            'from' => '2026-07-01',
            'to' => '2026-07-15',
            'supplier_id' => $supplier->id,
            'expense_concept_id' => $concept->id,
            'status' => 'paid',
        ]));

        $response->assertOk()
            ->assertSee($matching->reference)
            ->assertDontSee('GAS-AJENO')
            ->assertDontSee('GAS-PENDIENTE')
            ->assertSee('$300.00')
            ->assertSee('$0.00');
    }

    private function transaction(array $attributes = []): Transaction
    {
        return Transaction::create(array_merge([
            'client_id' => Client::firstOrCreate(['full_name' => 'Cliente gastos'], ['type' => 'active'])->id,
            'type' => 'expense',
            'scope' => 'operation',
            'transaction_date' => '2026-07-15',
            'amount' => 100,
            'status' => 'paid',
        ], $attributes));
    }

    private function user(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('manage payments'));

        return $user;
    }
}
