<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PhaseTwoManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['manage users', 'manage clients', 'view dashboard'] as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('cliente');
        $adminRole = Role::findOrCreate('admin');
        $adminRole->givePermissionTo(['manage users', 'manage clients', 'view dashboard']);

        $this->administrator = User::factory()->create(['is_active' => true]);
        $this->administrator->assignRole($adminRole);
    }

    public function test_client_users_are_hidden_and_cannot_be_managed_through_users_module(): void
    {
        $portal = User::factory()->create(['name' => 'Portal Cliente', 'is_active' => true]);
        $portal->assignRole('cliente');

        $this->actingAs($this->administrator)
            ->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('Portal Cliente');

        $this->actingAs($this->administrator)->get(route('users.edit', $portal))->assertNotFound();
        $this->actingAs($this->administrator)->delete(route('users.destroy', $portal))->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $portal->id]);
    }

    public function test_cliente_role_is_not_offered_or_accepted_and_phone_requires_digits(): void
    {
        $this->actingAs($this->administrator)
            ->get(route('users.create'))
            ->assertOk()
            ->assertDontSee('<option value="cliente"', false);

        $this->actingAs($this->administrator)
            ->post(route('users.store'), [
                'name' => 'Usuario inválido',
                'email' => 'invalid@example.com',
                'phone' => '55 1234',
                'password' => 'password',
                'password_confirmation' => 'different',
                'role' => 'cliente',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors(['phone', 'password', 'role']);
    }

    public function test_new_client_always_creates_a_synchronized_portal_user(): void
    {
        $response = $this->actingAs($this->administrator)
            ->post(route('clients.store'), [
                'type' => 'prospect',
                'full_name' => 'Ana Pérez',
                'company_name' => 'Eventos Ana',
                'email' => 'ana@example.com',
                'phone' => '5512345678',
                'portal_password' => 'secret123',
                'portal_password_confirmation' => 'secret123',
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('clients.index'));

        $client = Client::where('email', 'ana@example.com')->firstOrFail();
        $this->assertNotNull($client->user_id);
        $this->assertSame('Ana Pérez', $client->user->name);
        $this->assertSame('5512345678', $client->user->phone);
        $this->assertTrue($client->user->hasRole('cliente'));
        $this->assertTrue(Hash::check('secret123', $client->user->password));
    }

    public function test_historical_client_without_user_requires_known_password_and_is_linked_on_update(): void
    {
        $client = Client::create([
            'type' => 'prospect',
            'full_name' => 'Cliente histórico',
            'email' => null,
        ]);

        $payload = [
            'type' => 'active',
            'full_name' => 'Cliente histórico',
            'email' => 'historico@example.com',
            'phone' => '5511111111',
        ];

        $this->actingAs($this->administrator)
            ->put(route('clients.update', $client), $payload)
            ->assertSessionHasErrors('portal_password');

        $this->actingAs($this->administrator)
            ->put(route('clients.update', $client), [
                ...$payload,
                'portal_password' => 'secret123',
                'portal_password_confirmation' => 'secret123',
            ])
            ->assertSessionHasNoErrors();

        $client->refresh();
        $this->assertNotNull($client->user_id);
        $this->assertSame($client->email, $client->user->email);
        $this->assertTrue($client->user->hasRole('cliente'));
    }

    public function test_existing_client_user_stays_synchronized_without_changing_password(): void
    {
        $portal = User::factory()->create([
            'email' => 'before@example.com',
            'phone' => '5500000000',
            'password' => 'original-password',
            'is_active' => true,
        ]);
        $portal->assignRole('cliente');

        $client = Client::create([
            'user_id' => $portal->id,
            'type' => 'prospect',
            'full_name' => 'Nombre anterior',
            'email' => $portal->email,
        ]);

        $password = $portal->password;

        $this->actingAs($this->administrator)
            ->put(route('clients.update', $client), [
                'type' => 'active',
                'full_name' => 'Nombre actualizado',
                'email' => 'after@example.com',
                'phone' => '5599999999',
            ])
            ->assertSessionHasNoErrors();

        $portal->refresh();
        $this->assertSame('Nombre actualizado', $portal->name);
        $this->assertSame('after@example.com', $portal->email);
        $this->assertSame('5599999999', $portal->phone);
        $this->assertSame($password, $portal->password);
    }

    public function test_client_can_request_and_complete_password_reset_but_inactive_user_cannot_log_in(): void
    {
        Notification::fake();

        $portal = User::factory()->create(['is_active' => false]);
        $portal->assignRole('cliente');

        $this->post(route('password.email'), ['email' => $portal->email]);

        Notification::assertSentTo($portal, ResetPassword::class, function (ResetPassword $notification) use ($portal) {
            $this->post(route('password.store'), [
                'token' => $notification->token,
                'email' => $portal->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertSessionHasNoErrors();

            return true;
        });

        $this->post(route('login'), [
            'email' => $portal->email,
            'password' => 'new-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
