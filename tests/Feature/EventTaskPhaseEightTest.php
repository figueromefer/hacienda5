<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EventTaskPhaseEightTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_only_offers_active_internal_users_and_rejects_manipulated_assignees(): void
    {
        $manager = $this->userWithPermissions(['manage events']);
        $active = User::factory()->create(['name' => 'Interno activo', 'is_active' => true]);
        $inactive = User::factory()->create(['name' => 'Interno inactivo', 'is_active' => false]);
        $clientUser = User::factory()->create(['name' => 'Usuario cliente', 'is_active' => true]);
        Role::findOrCreate('cliente')->givePermissionTo([]);
        $clientUser->assignRole('cliente');
        $event = $this->event();

        $this->actingAs($manager)->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Interno activo')
            ->assertDontSee('Interno inactivo')
            ->assertDontSee('Usuario cliente');

        foreach ([$inactive, $clientUser] as $invalidUser) {
            $this->actingAs($manager)->post(route('events.tasks.store', $event), $this->payload([
                'assigned_to' => $invalidUser->id,
            ]))->assertSessionHasErrors('assigned_to');
        }

        $this->assertDatabaseCount('event_tasks', 0);
    }

    public function test_tasks_can_be_edited_completed_and_cancelled_without_deletion(): void
    {
        $manager = $this->userWithPermissions(['manage events']);
        $assignee = User::factory()->create(['is_active' => true]);
        $event = $this->event();
        $task = EventTask::create([
            'event_id' => $event->id,
            'title' => 'Confirmar montaje',
            'assigned_to' => $assignee->id,
            'due_date' => '2026-07-20 10:00:00',
            'status' => EventTask::STATUS_PENDING,
        ]);

        $this->actingAs($manager)->put(route('event-tasks.update', $task), $this->payload([
            'title' => 'Confirmar montaje final',
            'assigned_to' => $assignee->id,
            'due_date' => '2026-07-21 11:00:00',
        ]))->assertRedirect(route('events.show', $event));

        $this->assertDatabaseHas('event_tasks', [
            'id' => $task->id,
            'title' => 'Confirmar montaje final',
            'assigned_to' => $assignee->id,
        ]);

        $this->actingAs($assignee)->patch(route('event-tasks.complete', $task))->assertSessionHas('success');
        $this->assertSame(EventTask::STATUS_DONE, $task->fresh()->status);

        $task->update(['status' => EventTask::STATUS_PENDING]);
        $this->actingAs($assignee)->patch(route('event-tasks.cancel', $task))->assertSessionHas('success');
        $this->assertSame(EventTask::STATUS_CANCELLED, $task->fresh()->status);
        $this->assertDatabaseHas('event_tasks', ['id' => $task->id]);
        $this->actingAs($manager)->delete('/event-tasks/'.$task->id)->assertMethodNotAllowed();
    }

    public function test_only_manager_or_assignee_can_change_a_task(): void
    {
        $assignee = $this->userWithPermissions(['view dashboard']);
        $stranger = $this->userWithPermissions(['view dashboard']);
        $task = EventTask::create([
            'event_id' => $this->event()->id,
            'title' => 'Tarea protegida',
            'assigned_to' => $assignee->id,
            'status' => EventTask::STATUS_PENDING,
        ]);

        $this->actingAs($stranger)->get(route('event-tasks.edit', $task))->assertForbidden();
        $this->actingAs($stranger)->patch(route('event-tasks.complete', $task))->assertForbidden();
        $this->actingAs($assignee)->get(route('event-tasks.edit', $task))->assertOk();
        $this->actingAs($assignee)->put(route('event-tasks.update', $task), $this->payload([
            'title' => 'Tarea editada por responsable',
            'assigned_to' => $stranger->id,
            'origin' => 'dashboard',
        ]))->assertRedirect(route('dashboard'));
        $this->assertSame($assignee->id, $task->fresh()->assigned_to);
    }

    public function test_dashboard_shows_only_pending_tasks_assigned_to_the_authenticated_user_in_due_order(): void
    {
        $user = $this->userWithPermissions(['view dashboard', 'manage events']);
        $other = User::factory()->create(['is_active' => true]);
        $event = $this->event();

        $later = $this->task($event, $user, 'Tarea posterior', '2026-07-25 10:00:00');
        $soon = $this->task($event, $user, 'Tarea próxima', '2026-07-18 10:00:00');
        $this->task($event, $other, 'Tarea ajena', '2026-07-17 10:00:00');
        $this->task($event, $user, 'Tarea completada', '2026-07-16 10:00:00', EventTask::STATUS_DONE);

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $response->assertSeeInOrder([$soon->title, $later->title])
            ->assertDontSee('Tarea ajena')
            ->assertDontSee('Tarea completada')
            ->assertSee(route('event-tasks.edit', ['eventTask' => $soon, 'origin' => 'dashboard']), false)
            ->assertSee(route('event-tasks.complete', $soon), false)
            ->assertSee(route('event-tasks.cancel', $soon), false)
            ->assertSee(route('events.show', $event), false);
    }

    private function payload(array $attributes = []): array
    {
        return array_merge([
            'title' => 'Tarea de prueba',
            'due_date' => '2026-07-20 10:00:00',
            'status' => EventTask::STATUS_PENDING,
            'assigned_to' => null,
            'notes' => 'Notas',
        ], $attributes);
    }

    private function event(): Event
    {
        $client = Client::create(['type' => 'active', 'full_name' => 'Cliente tareas']);

        return Event::create([
            'client_id' => $client->id,
            'title' => 'Evento tareas',
            'event_type' => 'Boda',
            'status' => Event::STATUS_CONFIRMED,
            'event_date' => '2026-08-01',
        ]);
    }

    private function task(Event $event, User $user, string $title, string $dueDate, string $status = EventTask::STATUS_PENDING): EventTask
    {
        return EventTask::create([
            'event_id' => $event->id,
            'title' => $title,
            'assigned_to' => $user->id,
            'due_date' => $dueDate,
            'status' => $status,
        ]);
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo($permissions);

        return $user;
    }
}
