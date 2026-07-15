<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class QuotationPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_download_a_generated_pdf_with_the_expected_name(): void
    {
        $quotation = $this->quotation();

        $response = $this->actingAs($this->authorizedUser())->get(route('quotations.pdf', $quotation));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('cotizacion-cot-123.pdf');

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_user_without_permission_cannot_download_a_quotation_pdf(): void
    {
        Permission::findOrCreate('manage quotations');
        $quotation = $this->quotation();

        $this->actingAs(User::factory()->create())
            ->get(route('quotations.pdf', $quotation))
            ->assertForbidden();
    }

    public function test_missing_quotation_returns_not_found(): void
    {
        $this->actingAs($this->authorizedUser())
            ->get(route('quotations.pdf', 999999))
            ->assertNotFound();
    }

    public function test_download_button_appears_on_quotation_detail(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->authorizedUser())
            ->get(route('quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Descargar PDF')
            ->assertSee(route('quotations.pdf', $quotation), false);
    }

    public function test_download_option_appears_in_quotation_list_actions(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->authorizedUser())
            ->get(route('quotations.index'))
            ->assertOk()
            ->assertSee('Descargar PDF')
            ->assertSee(route('quotations.pdf', $quotation), false);
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('manage quotations'));

        return $user;
    }

    private function quotation(): Quotation
    {
        $client = Client::create(['type' => 'active', 'full_name' => 'Cliente PDF']);

        $quotation = Quotation::create([
            'client_id' => $client->id,
            'folio' => 'COT-123',
            'status' => 'draft',
            'subtotal' => 1500,
            'discount' => 100,
            'total' => 1400,
            'valid_until' => '2026-08-15',
            'notes' => 'Notas de la cotización.',
        ]);

        $quotation->items()->create([
            'description' => 'Renta del espacio',
            'quantity' => 2,
            'unit_price' => 750,
            'total' => 1500,
        ]);

        return $quotation;
    }
}
