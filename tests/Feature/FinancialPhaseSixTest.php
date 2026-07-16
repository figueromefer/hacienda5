<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Event;
use App\Models\Quotation;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinancialBalanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FinancialPhaseSixTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_calculation_covers_quotation_movement_and_overpayment_rules(): void
    {
        $client = $this->client();
        $event = $this->event($client, 99999);
        $otherEvent = $this->event($client, 88888);

        $balance = $this->balance($event);
        $this->assertSame('0.00', $balance['approved_quotation_total']);
        $this->assertSame('0.00', $balance['pending_receivable']);

        $approved = $this->quotation($client, $event, 'approved', 1000);
        $balance = $this->balance($event);
        $this->assertSame('1000.00', $balance['approved_quotation_total']);

        $this->quotation($client, $event, 'approved', 250);
        $this->quotation($client, $event, 'draft', 4000);
        $this->quotation($client, $event, 'rejected', 5000);
        $this->quotation($client, $otherEvent, 'approved', 9000);
        $balance = $this->balance($event);
        $this->assertSame('1250.00', $balance['approved_quotation_total']);

        $this->transaction($client, $event, Transaction::TYPE_INCOME, Transaction::STATUS_PAID, 400);
        $this->transaction($client, $event, Transaction::TYPE_INCOME, Transaction::STATUS_PENDING, 200);
        $this->transaction($client, $event, Transaction::TYPE_INCOME, Transaction::STATUS_CANCELLED, 300);
        $this->transaction($client, $event, Transaction::TYPE_EXPENSE, Transaction::STATUS_PAID, 75);
        $balance = $this->balance($event);
        $this->assertSame('400.00', $balance['paid_income']);
        $this->assertSame('75.00', $balance['paid_expenses']);
        $this->assertSame('850.00', $balance['pending_receivable']);
        $this->assertSame('0.00', $balance['overpayment']);
        $this->assertSame('325.00', $balance['cash_balance']);

        $this->transaction($client, $event, Transaction::TYPE_INCOME, Transaction::STATUS_PAID, 1000);
        $balance = $this->balance($event);
        $this->assertSame('0.00', $balance['pending_receivable']);
        $this->assertSame('150.00', $balance['overpayment']);

        $approved->update(['status' => 'rejected']);
        $balance = $this->balance($event);
        $this->assertSame('250.00', $balance['approved_quotation_total']);
        $this->assertSame('1150.00', $balance['overpayment']);
    }

    public function test_cancelling_a_paid_income_is_reflected_on_a_fresh_query(): void
    {
        $client = $this->client();
        $event = $this->event($client);
        $this->quotation($client, $event, 'approved', 1000);
        $income = $this->transaction($client, $event, Transaction::TYPE_INCOME, Transaction::STATUS_PAID, 400);

        $this->assertSame('600.00', $this->balance($event)['pending_receivable']);

        $income->update(['status' => Transaction::STATUS_CANCELLED]);
        $balance = $this->balance($event);

        $this->assertSame('0.00', $balance['paid_income']);
        $this->assertSame('1000.00', $balance['pending_receivable']);
        $this->assertSame(['0.00'], $balance['transactions']->pluck('running_balance')->all());
    }

    public function test_dashboard_client_and_portal_show_the_same_financial_source(): void
    {
        $user = User::factory()->create();
        foreach (['view dashboard', 'manage clients', 'access client portal'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission));
        }

        $client = $this->client(['user_id' => $user->id]);
        $event = $this->event($client, 99999);
        $this->quotation($client, $event, 'approved', 1000);
        $this->quotation($client, $event, 'draft', 8000);
        $this->transaction($client, $event, Transaction::TYPE_INCOME, Transaction::STATUS_PAID, 400);
        $this->transaction($client, $event, Transaction::TYPE_INCOME, Transaction::STATUS_PENDING, 300);
        $this->transaction($client, $event, Transaction::TYPE_INCOME, Transaction::STATUS_CANCELLED, 200);

        $dashboard = $this->actingAs($user)->get(route('dashboard'));
        $dashboard->assertOk()
            ->assertSeeText('Pendiente por cobrar')
            ->assertSeeText('$600.00');

        $expected = 'Costo aprobado: $1,000.00 · Cobrado: $400.00 · Pendiente: $600.00';
        $this->actingAs($user)->get(route('clients.show', $client))
            ->assertOk()
            ->assertSeeText($expected)
            ->assertDontSeeText('$99,999.00');
        $this->actingAs($user)->get(route('client.portal'))
            ->assertOk()
            ->assertSeeText('Costo aprobado: $1,000.00')
            ->assertSeeText('Cobrado: $400.00 · Pendiente: $600.00')
            ->assertDontSeeText('$99,999.00');
    }

    private function balance(Event $event): array
    {
        return app(FinancialBalanceCalculator::class)->forEvent(
            Event::with(['quotations', 'transactions'])->findOrFail($event->id),
        );
    }

    private function client(array $attributes = []): Client
    {
        return Client::create(array_merge([
            'type' => 'active',
            'full_name' => 'Cliente financiero',
        ], $attributes));
    }

    private function event(Client $client, float $legacyTotal = 0): Event
    {
        return Event::create([
            'client_id' => $client->id,
            'title' => 'Evento financiero',
            'event_type' => 'Social',
            'status' => Event::STATUS_CONFIRMED,
            'event_date' => '2026-09-20',
            'total_amount' => $legacyTotal,
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

    private function transaction(
        Client $client,
        Event $event,
        string $type,
        string $status,
        float $amount,
    ): Transaction {
        return Transaction::create([
            'client_id' => $client->id,
            'event_id' => $event->id,
            'type' => $type,
            'scope' => 'event',
            'transaction_date' => '2026-07-16',
            'amount' => $amount,
            'method' => 'transfer',
            'category' => 'Prueba financiera',
            'reference' => strtoupper(substr($type, 0, 3)).'-'.fake()->unique()->numerify('2026-######'),
            'status' => $status,
        ]);
    }
}
