<?php

namespace Tests\Feature;

use App\Models\ExpenseConcept;
use App\Models\Supplier;
use App\Models\SupplierPayable;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SupplierPayableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withSession(['_token' => 'test-token']);
    }

    public function test_creates_payable_and_requires_supplier(): void
    {
        $supplier = Supplier::create(['name' => 'Flores Cinco']);
        $this->actingAs($this->user())->post(route('supplier-payables.store'), $this->payload(['supplier_id' => $supplier->id]))
            ->assertRedirect();
        $this->assertDatabaseHas('supplier_payables', ['supplier_id' => $supplier->id, 'description' => 'Factura de flores', 'status' => 'pending']);

        $this->actingAs($this->user())->post(route('supplier-payables.store'), $this->payload(['supplier_id' => null]))
            ->assertSessionHasErrors('supplier_id');
    }

    public function test_partial_and_total_payments_calculate_balance_change_status_and_generate_gas_reference(): void
    {
        $payable = $this->payable();
        $user = $this->user();

        $this->actingAs($user)->post(route('supplier-payables.pay', $payable), $this->payment(400))->assertRedirect();
        $payable->refresh()->loadSum(['transactions as paid_amount_sum' => fn ($q) => $q->where('type', 'expense')->where('status', 'paid')], 'amount');
        $this->assertSame('partially_paid', $payable->status);
        $this->assertSame(400.0, $payable->paid_amount);
        $this->assertSame(600.0, $payable->balance);
        $payment = $payable->transactions()->firstOrFail();
        $this->assertSame('expense', $payment->type);
        $this->assertSame('paid', $payment->status);
        $this->assertSame($payable->supplier_id, $payment->supplier_id);
        $this->assertMatchesRegularExpression('/^GAS-2026-\d{6}$/', $payment->reference);

        $this->actingAs($user)->post(route('supplier-payables.pay', $payable), $this->payment(600))->assertRedirect();
        $this->assertSame('paid', $payable->refresh()->status);
        $this->assertSame(0.0, $payable->balance);
    }

    public function test_rejects_overpayment_and_payment_on_cancelled_payable(): void
    {
        $payable = $this->payable();
        $user = $this->user();
        $this->actingAs($user)->post(route('supplier-payables.pay', $payable), $this->payment(1000.01))->assertSessionHasErrors('amount');
        $payable->update(['status' => 'cancelled']);
        $this->actingAs($user)->post(route('supplier-payables.pay', $payable), $this->payment(100))->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_cancelling_preserves_historical_payments(): void
    {
        $payable = $this->payable();
        $user = $this->user();
        $this->actingAs($user)->post(route('supplier-payables.pay', $payable), $this->payment(200));
        $transaction = Transaction::firstOrFail();
        $this->actingAs($user)->patch(route('supplier-payables.cancel', $payable), ['_token' => 'test-token', 'confirm_cancel' => '1'])->assertRedirect();
        $this->assertSame('cancelled', $payable->refresh()->status);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => 'paid', 'supplier_payable_id' => $payable->id]);
    }

    public function test_archived_supplier_remains_visible_in_history_and_is_not_available_for_new_accounts(): void
    {
        $payable = $this->payable();
        $payable->supplier->update(['is_active' => false]);
        $other = Supplier::create(['name' => 'Archivado ajeno', 'is_active' => false]);

        $this->actingAs($this->user())->get(route('supplier-payables.show', $payable))->assertOk()->assertSee($payable->supplier->name);
        $this->actingAs($this->user())->get(route('supplier-payables.edit', $payable))->assertOk()->assertSee($payable->supplier->name)->assertDontSee($other->name);
        $this->actingAs($this->user())->get(route('supplier-payables.create'))->assertOk()->assertDontSee($payable->supplier->name);
    }

    public function test_main_filters_and_filtered_summary_work(): void
    {
        $supplier = Supplier::create(['name' => 'Proveedor objetivo']);
        $concept = ExpenseConcept::create(['name' => 'Mantelería']);
        $matching = SupplierPayable::create($this->payload(['supplier_id' => $supplier->id, 'expense_concept_id' => $concept->id, 'due_date' => '2026-07-01', 'total_amount' => 700]));
        SupplierPayable::create($this->payload(['supplier_id' => Supplier::create(['name' => 'Otro'])->id, 'description' => 'No mostrar', 'total_amount' => 900]));

        $response = $this->actingAs($this->user())->get(route('supplier-payables.index', ['q' => 'Factura', 'supplier_id' => $supplier->id, 'expense_concept_id' => $concept->id, 'status' => 'pending', 'overdue' => 1, 'due_from' => '2026-06-01', 'due_to' => '2026-07-10']));
        $response->assertOk()->assertSee($matching->description)->assertDontSee('No mostrar')->assertSee('$700.00')->assertSee('Vencidas')->assertSee('1');
    }

    public function test_supplier_detail_shows_payable_summary(): void
    {
        $supplier = Supplier::create(['name' => 'Proveedor resumen']);
        SupplierPayable::create($this->payload(['supplier_id' => $supplier->id, 'total_amount' => 800]));
        SupplierPayable::create($this->payload(['supplier_id' => $supplier->id, 'description' => 'Parcial', 'total_amount' => 500, 'status' => 'partially_paid']));
        Transaction::create(['supplier_payable_id' => SupplierPayable::latest('id')->value('id'), 'supplier_id' => $supplier->id, 'type' => 'expense', 'scope' => 'operation', 'transaction_date' => '2026-07-15', 'amount' => 200, 'status' => 'paid']);

        $this->actingAs($this->user())->get(route('suppliers.show', $supplier))->assertOk()->assertSee('Cuentas por pagar')->assertSee('$1,100.00')->assertSee(route('supplier-payables.index', ['supplier_id' => $supplier->id]), false);
    }

    private function payable(): SupplierPayable
    {
        $supplier = Supplier::create(['name' => 'Proveedor pagos']);

        return SupplierPayable::create($this->payload(['supplier_id' => $supplier->id]));
    }

    private function payload(array $attributes = []): array
    {
        return array_merge(['_token' => 'test-token', 'supplier_id' => null, 'description' => 'Factura de flores', 'total_amount' => 1000, 'due_date' => '2026-07-20', 'status' => 'pending'], $attributes);
    }

    private function payment(float $amount): array
    {
        return ['_token' => 'test-token', 'transaction_date' => '2026-07-15', 'amount' => $amount, 'method' => 'transferencia', 'description' => 'Pago proveedor'];
    }

    private function user(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('manage payments'));
        $user->givePermissionTo(Permission::findOrCreate('manage suppliers'));

        return $user;
    }
}
