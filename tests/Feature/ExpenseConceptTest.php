<?php

namespace Tests\Feature;

use App\Models\ExpenseConcept;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseConceptTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_access_catalog_and_unauthorized_user_cannot(): void
    {
        $this->actingAs($this->authorizedUser())->get(route('expense-concepts.index'))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('expense-concepts.index'))->assertForbidden();
    }

    public function test_active_listing_excludes_archived_concepts(): void
    {
        ExpenseConcept::create(['name' => 'Limpieza', 'is_active' => true]);
        ExpenseConcept::create(['name' => 'Concepto archivado', 'is_active' => false]);

        $this->actingAs($this->authorizedUser())->get(route('expense-concepts.index'))
            ->assertSee('Limpieza')
            ->assertDontSee('Concepto archivado');
    }

    public function test_valid_concept_is_created_with_normalized_spaces(): void
    {
        $this->actingAs($this->authorizedUser())->post(route('expense-concepts.store'), [
            'name' => '  Renta   de   mobiliario ',
            'description' => '  Mesas   y sillas  ',
        ])->assertRedirect(route('expense-concepts.index'));

        $this->assertDatabaseHas('expense_concepts', [
            'name' => 'Renta de mobiliario',
            'description' => 'Mesas y sillas',
            'is_active' => true,
        ]);
    }

    public function test_name_is_required(): void
    {
        $this->actingAs($this->authorizedUser())->post(route('expense-concepts.store'), ['name' => '   '])
            ->assertSessionHasErrors(['name']);
    }

    public function test_duplicate_name_is_rejected_ignoring_case_spaces_and_archived_state(): void
    {
        ExpenseConcept::create(['name' => 'Renta de mobiliario', 'is_active' => false]);

        $this->actingAs($this->authorizedUser())->post(route('expense-concepts.store'), [
            'name' => '  RENTA   DE MOBILIARIO ',
        ])->assertSessionHasErrors(['name']);

        $this->assertDatabaseCount('expense_concepts', 1);
    }

    public function test_same_record_can_be_edited_without_false_duplicate(): void
    {
        $concept = ExpenseConcept::create(['name' => 'Transporte', 'is_active' => true]);

        $this->actingAs($this->authorizedUser())->put(route('expense-concepts.update', $concept), [
            'name' => '  TRANSPORTE ',
            'description' => 'Traslados',
        ])->assertRedirect(route('expense-concepts.index'));

        $this->assertDatabaseHas('expense_concepts', ['id' => $concept->id, 'name' => 'TRANSPORTE']);
    }

    public function test_concept_can_be_archived_viewed_and_restored(): void
    {
        $concept = ExpenseConcept::create(['name' => 'Viáticos', 'is_active' => true]);
        $user = $this->authorizedUser();

        $this->actingAs($user)->delete(route('expense-concepts.destroy', $concept))
            ->assertRedirect(route('expense-concepts.index'));
        $this->assertDatabaseHas('expense_concepts', ['id' => $concept->id, 'is_active' => false]);

        $this->actingAs($user)->get(route('expense-concepts.archived'))->assertOk()->assertSee('Viáticos');

        $this->actingAs($user)->patch(route('expense-concepts.restore', $concept))
            ->assertRedirect(route('expense-concepts.archived'));
        $this->assertDatabaseHas('expense_concepts', ['id' => $concept->id, 'is_active' => true]);
    }

    public function test_search_matches_name_and_description(): void
    {
        ExpenseConcept::create(['name' => 'Flores', 'description' => 'Decoración floral', 'is_active' => true]);
        ExpenseConcept::create(['name' => 'Audio', 'description' => 'Bocinas profesionales', 'is_active' => true]);

        $user = $this->authorizedUser();
        $this->actingAs($user)->get(route('expense-concepts.index', ['search' => 'Flores']))
            ->assertSee('Flores')->assertDontSee('Audio');
        $this->actingAs($user)->get(route('expense-concepts.index', ['search' => 'Bocinas']))
            ->assertSee('Audio')->assertDontSee('Flores');
    }

    public function test_action_controls_follow_the_accessible_responsive_pattern(): void
    {
        $active = ExpenseConcept::create(['name' => 'Montaje', 'is_active' => true]);
        $archived = ExpenseConcept::create(['name' => 'Desmontaje', 'is_active' => false]);
        $user = $this->authorizedUser();

        $this->actingAs($user)->get(route('expense-concepts.index'))
            ->assertOk()
            ->assertSee('responsive-table', false)
            ->assertSee('aria-label="Ver concepto Montaje"', false)
            ->assertSee('aria-label="Editar concepto Montaje"', false)
            ->assertSee('aria-label="Archivar concepto Montaje"', false)
            ->assertSee('min-h-11', false)
            ->assertSee(route('expense-concepts.show', $active), false);

        $this->actingAs($user)->get(route('expense-concepts.archived'))
            ->assertOk()
            ->assertSee('responsive-table', false)
            ->assertSee('aria-label="Restaurar concepto Desmontaje"', false)
            ->assertSee(route('expense-concepts.restore', $archived), false);
    }

    public function test_navigation_link_is_visible_only_with_permission_in_desktop_and_mobile(): void
    {
        Permission::findOrCreate('view dashboard');
        $authorized = $this->authorizedUser();
        $authorized->givePermissionTo('view dashboard');
        $unauthorized = User::factory()->create();
        $unauthorized->givePermissionTo('view dashboard');

        $authorizedHtml = $this->actingAs($authorized)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertSame(2, substr_count($authorizedHtml, 'Conceptos de gasto'));

        $unauthorizedHtml = $this->actingAs($unauthorized)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Conceptos de gasto', $unauthorizedHtml);
    }

    public function test_permission_seeder_assigns_new_permission_only_to_admin_roles_and_is_idempotent(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertTrue(Role::findByName('super_admin')->hasPermissionTo('manage expense concepts'));
        $this->assertTrue(Role::findByName('admin')->hasPermissionTo('manage expense concepts'));
        $this->assertFalse(Role::findByName('ventas')->hasPermissionTo('manage expense concepts'));
        $this->assertFalse(Role::findByName('operaciones')->hasPermissionTo('manage expense concepts'));
        $this->assertFalse(Role::findByName('cliente')->hasPermissionTo('manage expense concepts'));
        $this->assertSame(1, Permission::where('name', 'manage expense concepts')->count());
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create();
        $permission = Permission::findOrCreate('manage expense concepts');
        $user->givePermissionTo($permission);

        return $user;
    }
}
