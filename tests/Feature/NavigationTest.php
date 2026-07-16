<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogs_only_contains_individually_authorized_options_on_desktop_and_mobile(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        foreach (['view dashboard', 'manage clients', 'manage users'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo(['view dashboard', 'manage clients']);

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertGreaterThanOrEqual(2, substr_count($html, 'Catálogos'));
        $this->assertSame(2, substr_count($html, 'href="'.route('clients.index').'"'));
        $this->assertStringNotContainsString('href="'.route('users.index').'"', $html);
        $this->assertStringContainsString('aria-controls="mobile-catalogs"', $html);
        $this->assertStringNotContainsString('href="'.route('expenses.index').'"', $html);
    }

    public function test_catalogs_is_hidden_without_catalog_permissions_and_suppliers_remains_primary(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        foreach (['view dashboard', 'manage suppliers'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo(['view dashboard', 'manage suppliers']);

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Catálogos', $html);
        $this->assertSame(2, substr_count($html, 'href="'.route('suppliers.index').'"'));
    }

    public function test_dashboard_quick_actions_respect_permissions(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        foreach (['view dashboard', 'manage clients', 'manage events', 'manage quotations', 'manage payments'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo(['view dashboard', 'manage clients', 'manage payments']);

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $response->assertSee('Nuevo cliente')
            ->assertSee('Nuevo movimiento')
            ->assertDontSee('Nuevo evento')
            ->assertDontSee('Nueva cotización');
    }

    public function test_profile_link_is_visible_in_user_dropdown_and_mobile_navigation(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Permission::findOrCreate('view dashboard');
        $user->givePermissionTo('view dashboard');

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'href="'.route('profile.edit').'"'));
        $this->assertSame(2, substr_count($html, 'Editar perfil'));
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'Cerrar sesión'));
    }
}
