<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_links_are_available_in_desktop_and_mobile_navigation(): void
    {
        $user = User::factory()->create();

        Permission::create(['name' => 'view dashboard']);
        Permission::create(['name' => 'manage clients']);
        Permission::create(['name' => 'manage users']);

        $user->givePermissionTo(['view dashboard', 'manage clients']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();

        $html = $response->getContent();

        // El dashboard aparece en ambos menús y también es el destino del logotipo.
        $this->assertSame(3, substr_count($html, 'href="'.route('dashboard').'"'));
        $this->assertSame(2, substr_count($html, 'href="'.route('clients.index').'"'));
        $this->assertStringNotContainsString('href="'.route('users.index').'"', $html);
        $this->assertStringContainsString('id="mobile-navigation"', $html);
        $this->assertStringContainsString('aria-controls="mobile-navigation"', $html);
    }

    public function test_mobile_navigation_renders_every_authorized_item_in_its_scrollable_panel(): void
    {
        $permissions = [
            'view dashboard',
            'manage users',
            'manage clients',
            'manage services',
            'manage events',
            'manage quotations',
            'manage payments',
            'view calendar',
        ];

        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $user->givePermissionTo($permissions);

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        foreach (['Usuarios', 'Clientes', 'Servicios', 'Eventos', 'Cotizaciones', 'Movimientos', 'Calendario'] as $label) {
            $this->assertSame(2, substr_count($html, $label));
        }

        $this->assertStringContainsString('mobile-navigation-panel', $html);

        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('max-height: calc(100dvh - 5rem)', $css);
        $this->assertStringContainsString('overflow-y: auto', $css);
        $this->assertStringContainsString('padding-bottom: env(safe-area-inset-bottom, 0)', $css);
    }
}
