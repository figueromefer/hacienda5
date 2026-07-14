<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_supplier_list_and_details(): void
    {
        $user = $this->authorizedUser();
        $supplier = Supplier::create(['name' => 'Flores La Victoria']);

        $this->actingAs($user)->get(route('suppliers.index'))
            ->assertOk()
            ->assertSee('Flores La Victoria');

        $this->actingAs($user)->get(route('suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('Detalle del proveedor');
    }

    public function test_user_without_permission_cannot_manage_suppliers(): void
    {
        Permission::findOrCreate('manage suppliers');
        $user = User::factory()->create();
        $supplier = Supplier::create(['name' => 'Proveedor protegido']);

        $this->actingAs($user)->get(route('suppliers.index'))->assertForbidden();
        $this->actingAs($user)->post(route('suppliers.store'), ['name' => 'No autorizado'])->assertForbidden();
        $this->actingAs($user)->put(route('suppliers.update', $supplier), ['name' => 'No autorizado'])->assertForbidden();
        $this->actingAs($user)->delete(route('suppliers.destroy', $supplier))->assertForbidden();
    }

    public function test_supplier_can_be_created_and_rfc_is_normalized(): void
    {
        $response = $this->actingAs($this->authorizedUser())->post(route('suppliers.store'), [
            'name' => 'Banquetes del Centro',
            'contact_name' => 'María López',
            'phone' => '555-123-4567',
            'email' => 'contacto@example.com',
            'rfc' => '  bdc 010203 ab1 ',
            'address' => 'Calle Principal 10',
            'notes' => 'Entrega por acceso norte.',
        ]);

        $response->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Banquetes del Centro',
            'rfc' => 'BDC010203AB1',
            'is_active' => true,
        ]);
    }

    public function test_supplier_validation_requires_name_and_validates_email_only_when_present(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)->post(route('suppliers.store'), [
            'name' => '',
            'email' => 'correo-no-valido',
        ])->assertSessionHasErrors(['name', 'email']);

        $this->actingAs($user)->post(route('suppliers.store'), [
            'name' => 'Proveedor sin correo',
            'email' => '',
        ])->assertRedirect(route('suppliers.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('suppliers', ['name' => 'Proveedor sin correo', 'email' => null]);
    }

    public function test_supplier_can_be_updated_without_changing_archive_state(): void
    {
        $supplier = Supplier::create([
            'name' => 'Nombre inicial',
            'is_active' => false,
        ]);

        $this->actingAs($this->authorizedUser())->put(route('suppliers.update', $supplier), [
            'name' => 'Nombre actualizado',
            'contact_name' => 'Contacto nuevo',
        ])->assertRedirect(route('suppliers.show', $supplier));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Nombre actualizado',
            'contact_name' => 'Contacto nuevo',
            'is_active' => false,
        ]);
    }

    public function test_supplier_can_be_archived_and_restored_without_being_deleted(): void
    {
        $user = $this->authorizedUser();
        $supplier = Supplier::create(['name' => 'Proveedor archivable']);

        $this->actingAs($user)->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'is_active' => false]);

        $this->actingAs($user)->patch(route('suppliers.restore', $supplier))
            ->assertRedirect(route('suppliers.index', ['status' => 'archived']));

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'is_active' => true]);
    }

    public function test_search_matches_supported_fields_and_normal_list_excludes_archived_records(): void
    {
        $user = $this->authorizedUser();
        Supplier::create(['name' => 'Activo sin coincidencia']);
        Supplier::create([
            'name' => 'Audio Profesional',
            'contact_name' => 'Carlos Sonido',
            'phone' => '5551112233',
            'email' => 'audio@example.com',
            'rfc' => 'AUP010101AA1',
        ]);
        Supplier::create([
            'name' => 'Archivado encontrado',
            'email' => 'archivo@example.com',
            'is_active' => false,
        ]);

        foreach (['Audio Profesional', 'Carlos Sonido', '5551112233', 'audio@example.com', 'AUP010101AA1'] as $search) {
            $this->actingAs($user)->get(route('suppliers.index', ['q' => $search]))
                ->assertOk()
                ->assertSee('Audio Profesional');
        }

        $this->actingAs($user)->get(route('suppliers.index'))
            ->assertSee('Activo sin coincidencia')
            ->assertDontSee('Archivado encontrado');

        $this->actingAs($user)->get(route('suppliers.index', ['status' => 'archived']))
            ->assertSee('Archivado encontrado')
            ->assertDontSee('Activo sin coincidencia');
    }

    public function test_supplier_navigation_is_visible_in_desktop_and_mobile_only_with_permission(): void
    {
        Permission::findOrCreate('view dashboard');
        Permission::findOrCreate('manage suppliers');
        $authorized = User::factory()->create();
        $authorized->givePermissionTo(['view dashboard', 'manage suppliers']);

        $html = $this->actingAs($authorized)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'href="'.route('suppliers.index').'"'));

        $unauthorized = User::factory()->create();
        $unauthorized->givePermissionTo('view dashboard');
        $html = $this->actingAs($unauthorized)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringNotContainsString('href="'.route('suppliers.index').'"', $html);
    }

    public function test_permission_seeder_assigns_supplier_management_to_administrative_roles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertTrue(Role::findByName('super_admin')->hasPermissionTo('manage suppliers'));
        $this->assertTrue(Role::findByName('admin')->hasPermissionTo('manage suppliers'));
        $this->assertFalse(Role::findByName('ventas')->hasPermissionTo('manage suppliers'));
        $this->assertFalse(Role::findByName('operaciones')->hasPermissionTo('manage suppliers'));
    }

    private function authorizedUser(): User
    {
        $permission = Permission::findOrCreate('manage suppliers');
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        return $user;
    }
}
