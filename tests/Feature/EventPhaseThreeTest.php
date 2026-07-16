<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Event;
use App\Models\Quotation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EventPhaseThreeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['manage events', 'manage documents', 'manage payments'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->givePermissionTo(['manage events', 'manage documents', 'manage payments']);

        $this->client = Client::create([
            'type' => 'active',
            'full_name' => 'Cliente de prueba',
            'email' => 'eventos@example.com',
        ]);
    }

    public function test_event_money_is_normalized_and_manual_total_is_not_accepted(): void
    {
        $this->actingAs($this->user)
            ->post(route('events.store'), [
                'client_id' => $this->client->id,
                'title' => 'Boda',
                'event_type' => 'Boda',
                'status' => Event::STATUS_TENTATIVE,
                'event_date' => '2026-09-20',
                'budget_estimate' => '$ 125,430.50',
                'total_amount' => '999999.99',
            ])
            ->assertSessionHasNoErrors();

        $event = Event::firstOrFail();
        $this->assertSame('125430.50', (string) $event->budget_estimate);
        $this->assertSame('0.00', (string) $event->total_amount);
    }

    public function test_event_index_searches_related_client_and_preserves_query_string(): void
    {
        $match = $this->event(['title' => 'Boda Jardín']);
        foreach (range(1, 15) as $index) {
            $this->event(['title' => 'Evento coincidente '.$index]);
        }
        $otherClient = Client::create(['type' => 'active', 'full_name' => 'Otro cliente']);
        $this->event(['client_id' => $otherClient->id, 'title' => 'Conferencia']);

        $response = $this->actingAs($this->user)
            ->get(route('events.index', ['search' => 'Cliente de prueba']))
            ->assertOk()
            ->assertSee($match->title)
            ->assertDontSee('Conferencia')
            ->assertSee('search=Cliente%20de%20prueba', false);

        $response->assertSee($match->status_label);
    }

    public function test_event_profile_uses_only_approved_quotations_for_cost_and_pending(): void
    {
        $event = $this->event();

        Quotation::create([
            'client_id' => $this->client->id,
            'event_id' => $event->id,
            'status' => 'approved',
            'subtotal' => 1500,
            'total' => 1500,
        ]);
        Quotation::create([
            'client_id' => $this->client->id,
            'event_id' => $event->id,
            'status' => 'draft',
            'subtotal' => 9000,
            'total' => 9000,
        ]);

        $this->actingAs($this->user)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Costo evento')
            ->assertSee('$1,500.00')
            ->assertSee('Pendiente por cobrar')
            ->assertSee(route('quotations.index', ['event_id' => $event->id]), false);
    }

    public function test_document_from_event_is_preselected_and_manipulated_client_is_rejected(): void
    {
        Storage::fake('public');

        $event = $this->event();
        $otherClient = Client::create(['type' => 'active', 'full_name' => 'Cliente ajeno']);

        $this->actingAs($this->user)
            ->get(route('documents.create', ['event_id' => $event->id]))
            ->assertOk()
            ->assertSee($event->title)
            ->assertSee($this->client->full_name)
            ->assertSee('name="event_id" value="'.$event->id.'"', false);

        $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'client_id' => $otherClient->id,
                'event_id' => $event->id,
                'category' => 'other',
                'file' => UploadedFile::fake()->create('archivo.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('client_id');

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_event_movement_can_be_cancelled_without_deleting_it(): void
    {
        $event = $this->event();
        $transaction = Transaction::create([
            'client_id' => $this->client->id,
            'event_id' => $event->id,
            'type' => Transaction::TYPE_INCOME,
            'scope' => 'event',
            'transaction_date' => '2026-09-01',
            'amount' => 500,
            'status' => 'paid',
        ]);

        $this->actingAs($this->user)
            ->patch(route('transactions.cancel', $transaction))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => 'cancelled']);

        $this->actingAs($this->user)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Cancelado')
            ->assertSee('$0.00');
    }

    private function event(array $attributes = []): Event
    {
        return Event::create([
            'client_id' => $attributes['client_id'] ?? $this->client->id,
            'title' => $attributes['title'] ?? 'Evento de prueba',
            'event_type' => 'Social',
            'status' => Event::STATUS_CONFIRMED,
            'event_date' => '2026-09-20',
            'budget_estimate' => 2000,
        ]);
    }
}
