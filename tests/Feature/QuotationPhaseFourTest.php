<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Event;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class QuotationPhaseFourTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_searches_by_spanish_status_and_preserves_filters_in_pagination(): void
    {
        $client = $this->client('Cliente buscable');
        $event = $this->event($client, 'Evento filtrado');
        $approved = $this->quotation($client, $event, 'COT-HISTORICO', 'approved');
        foreach (range(1, 15) as $index) {
            $this->quotation($client, $event, "COT-APROBADA-{$index}", 'approved');
        }
        $this->quotation($client, null, 'COT-BORRADOR', 'draft');

        $response = $this->actingAs($this->authorizedUser())
            ->get(route('quotations.index', ['event_id' => $event->id, 'search' => 'Aprobada']));

        $response->assertOk()
            ->assertSee($approved->folio)
            ->assertDontSee('COT-BORRADOR')
            ->assertSee('Aprobada');

        parse_str(parse_url($response->viewData('quotations')->url(2), PHP_URL_QUERY), $paginationQuery);
        $this->assertSame((string) $event->id, $paginationQuery['event_id']);
        $this->assertSame('Aprobada', $paginationQuery['search']);
    }

    public function test_create_form_exposes_filtered_event_ui_and_event_summary_fields(): void
    {
        $client = $this->client('Cliente del evento');
        $event = $this->event($client, 'Boda de verano');

        $response = $this->actingAs($this->authorizedUser())
            ->withSession([
                '_old_input' => [
                    'client_id' => (string) $client->id,
                    'event_id' => (string) $event->id,
                ],
            ])
            ->get(route('quotations.create'));

        $response->assertOk()
            ->assertSee('Sin evento')
            ->assertSee('Información del evento')
            ->assertSee('Presupuesto estimado')
            ->assertSee('events.filter(event => event.client_id === clientSelect.value)', false)
            ->assertSee('renderEventOptions(selectedEventId)', false);
    }

    public function test_index_searches_by_folio_client_and_event(): void
    {
        $client = $this->client('Mariana Buscable');
        $event = $this->event($client, 'Graduación Buscable');
        $quotation = $this->quotation($client, $event, 'COT-FOLIO-BUSCABLE');
        $user = $this->authorizedUser();

        foreach (['FOLIO-BUSCABLE', 'Mariana Buscable', 'Graduación Buscable'] as $search) {
            $this->actingAs($user)
                ->get(route('quotations.index', ['search' => $search]))
                ->assertOk()
                ->assertSee($quotation->folio);
        }
    }

    public function test_store_rejects_an_event_that_belongs_to_another_client(): void
    {
        $client = $this->client('Cliente correcto');
        $otherEvent = $this->event($this->client('Otro cliente'), 'Evento ajeno');

        $this->actingAs($this->authorizedUser())
            ->post(route('quotations.store'), $this->payload($client, $otherEvent))
            ->assertSessionHasErrors('event_id');

        $this->assertDatabaseCount('quotations', 0);
    }

    public function test_update_rejects_an_event_that_belongs_to_another_client(): void
    {
        $client = $this->client('Cliente original');
        $event = $this->event($client, 'Evento original');
        $quotation = $this->quotation($client, $event, 'COT-ANTIGUA');
        $otherClient = $this->client('Cliente nuevo');

        $this->actingAs($this->authorizedUser())
            ->put(route('quotations.update', $quotation), $this->payload($otherClient, $event))
            ->assertSessionHasErrors('event_id');

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'client_id' => $client->id,
            'event_id' => $event->id,
        ]);
    }

    public function test_store_normalizes_money_recalculates_totals_and_generates_short_folio(): void
    {
        $client = $this->client('Cliente cotización');
        $event = $this->event($client, 'Evento cotización');
        $payload = $this->payload($client, $event);
        $payload['discount'] = '$ 1,000.25';
        $payload['items'] = [
            ['description' => 'Servicio uno', 'quantity' => 2, 'unit_price' => '$ 1,250.50'],
            ['description' => 'Servicio dos', 'quantity' => 1, 'unit_price' => '500.25'],
        ];

        $this->actingAs($this->authorizedUser())
            ->post(route('quotations.store'), $payload)
            ->assertRedirect(route('quotations.index'));

        $quotation = Quotation::with('items')->sole();

        $this->assertSame('C-'.str_pad((string) $quotation->id, 6, '0', STR_PAD_LEFT), $quotation->folio);
        $this->assertSame('3001.25', $quotation->subtotal);
        $this->assertSame('1000.25', $quotation->discount);
        $this->assertSame('2001.00', $quotation->total);
        $this->assertSame(['2501.00', '500.25'], $quotation->items->pluck('total')->all());
    }

    public function test_updating_a_historical_quotation_preserves_its_folio(): void
    {
        $client = $this->client('Cliente histórico');
        $quotation = $this->quotation($client, null, 'COT-20200101010101');

        $this->actingAs($this->authorizedUser())
            ->put(route('quotations.update', $quotation), $this->payload($client))
            ->assertRedirect(route('quotations.index'));

        $this->assertSame('COT-20200101010101', $quotation->fresh()->folio);
    }

    public function test_pdf_template_uses_local_logo_and_page_safe_sections(): void
    {
        $quotation = $this->quotation($this->client('Cliente PDF'), null, 'C-000123');
        $quotation->load(['client', 'event', 'items']);

        $html = view('quotations.pdf', [
            'quotation' => $quotation,
            'logoPath' => public_path('images/hacienda-cinco-logo.png'),
        ])->render();

        $this->assertStringContainsString(public_path('images/hacienda-cinco-logo.png'), $html);
        $this->assertStringContainsString('page-break-inside: avoid', $html);
        $this->assertStringContainsString('Hacienda Cinco', $html);
    }

    public function test_pdf_renders_with_one_or_many_items_and_zero_or_positive_discount(): void
    {
        $user = $this->authorizedUser();

        foreach ([[1, '0.00'], [36, '125.50']] as [$itemCount, $discount]) {
            $quotation = $this->quotation($this->client("Cliente {$itemCount}"), null, "C-PDF-{$itemCount}", discount: $discount);
            $quotation->items()->delete();

            for ($index = 1; $index <= $itemCount; $index++) {
                $quotation->items()->create([
                    'description' => "Concepto {$index}",
                    'quantity' => 1,
                    'unit_price' => '100.00',
                    'total' => '100.00',
                ]);
            }

            $this->actingAs($user)
                ->get(route('quotations.pdf', $quotation))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('manage quotations'));

        return $user;
    }

    private function client(string $name): Client
    {
        return Client::create([
            'type' => 'active',
            'full_name' => $name,
        ]);
    }

    private function event(Client $client, string $title): Event
    {
        return Event::create([
            'client_id' => $client->id,
            'title' => $title,
            'event_type' => 'Boda',
            'status' => 'confirmed',
            'event_date' => '2026-09-20',
            'guest_count' => 120,
            'budget_estimate' => '150000.00',
        ]);
    }

    private function quotation(
        Client $client,
        ?Event $event,
        string $folio,
        string $status = 'draft',
        string $discount = '0.00',
    ): Quotation {
        $quotation = Quotation::create([
            'client_id' => $client->id,
            'event_id' => $event?->id,
            'folio' => $folio,
            'status' => $status,
            'subtotal' => '100.00',
            'discount' => $discount,
            'total' => '100.00',
        ]);
        $quotation->items()->create([
            'description' => 'Concepto inicial',
            'quantity' => 1,
            'unit_price' => '100.00',
            'total' => '100.00',
        ]);

        return $quotation;
    }

    private function payload(Client $client, ?Event $event = null): array
    {
        return [
            'client_id' => $client->id,
            'event_id' => $event?->id,
            'status' => 'draft',
            'valid_until' => '2026-10-20',
            'discount' => '0.00',
            'notes' => 'Notas',
            'items' => [
                [
                    'service_id' => null,
                    'description' => 'Servicio',
                    'quantity' => 1,
                    'unit_price' => '100.00',
                ],
            ],
        ];
    }
}
