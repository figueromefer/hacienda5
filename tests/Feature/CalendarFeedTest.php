<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CalendarFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_includes_supported_statuses_once_with_consistent_visual_data(): void
    {
        $user = $this->userWithPermissions(['view calendar']);
        $client = $this->client();

        $events = collect([
            Event::STATUS_RESERVED,
            Event::STATUS_TENTATIVE,
            Event::STATUS_CONFIRMED,
            Event::STATUS_CANCELLED,
        ])->map(fn (string $status) => $this->event($client, [
            'title' => 'Evento '.$status,
            'status' => $status,
        ]));

        $response = $this->actingAs($user)->getJson(route('calendar.feed', [
            'start' => '2026-07-01T00:00:00-06:00',
            'end' => '2026-08-01T00:00:00-06:00',
        ]));

        $response->assertOk()->assertJsonCount(4);

        $ids = collect($response->json())->pluck('id');

        $this->assertCount(4, $ids->unique());

        foreach ($events as $event) {
            $response->assertJsonFragment([
                'id' => (string) $event->id,
                'status' => $event->status,
                'statusLabel' => $event->status_label,
                'client' => $client->full_name,
                'eventType' => $event->event_type,
            ]);
        }

        $cancelled = collect($response->json())->firstWhere('extendedProps.status', Event::STATUS_CANCELLED);

        $this->assertSame(['calendar-event-cancelled'], $cancelled['classNames']);
        $this->assertSame('#6b7280', $cancelled['backgroundColor']);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_feed_preserves_mexico_local_dates_and_handles_all_day_and_overnight_events(): void
    {
        $user = $this->userWithPermissions(['view calendar']);
        $client = $this->client();

        $allDay = $this->event($client, [
            'title' => 'Evento sin hora',
            'event_date' => '2026-07-14',
            'start_time' => null,
            'end_time' => null,
        ]);

        $overnight = $this->event($client, [
            'title' => 'Evento nocturno',
            'event_date' => '2026-07-23',
            'start_time' => '20:17:00',
            'end_time' => '02:35:00',
        ]);

        $events = collect($this->actingAs($user)->getJson(route('calendar.feed'))->assertOk()->json());

        $allDayPayload = $events->firstWhere('id', (string) $allDay->id);
        $overnightPayload = $events->firstWhere('id', (string) $overnight->id);

        $this->assertTrue($allDayPayload['allDay']);
        $this->assertSame('Evento sin hora', $allDayPayload['title']);
        $this->assertSame('2026-07-14', $allDayPayload['start']);
        $this->assertNull($allDayPayload['end']);
        $this->assertNull($allDayPayload['extendedProps']['startTime']);
        $this->assertNull($allDayPayload['extendedProps']['endTime']);
        $this->assertFalse($overnightPayload['allDay']);
        $this->assertSame('Evento nocturno', $overnightPayload['title']);
        $this->assertSame('2026-07-23T20:17:00', $overnightPayload['start']);
        $this->assertSame('2026-07-24T02:35:00', $overnightPayload['end']);
        $this->assertSame('20:17:00', $overnightPayload['extendedProps']['startTime']);
        $this->assertSame('02:35:00', $overnightPayload['extendedProps']['endTime']);
        $this->assertSame('America/Mexico_City', config('app.timezone'));
    }

    public function test_new_reserved_event_is_immediately_available_in_feed(): void
    {
        $user = $this->userWithPermissions(['manage events', 'view calendar']);
        $client = $this->client();

        $this->actingAs($user)->post(route('events.store'), [
            'client_id' => $client->id,
            'title' => 'Boda recién apartada',
            'event_type' => 'Boda',
            'status' => Event::STATUS_RESERVED,
            'event_date' => '2026-09-12',
            'start_time' => '17:00',
            'end_time' => '23:00',
            'total_amount' => 0,
        ])->assertRedirect(route('events.index'));

        $this->actingAs($user)
            ->getJson(route('calendar.feed'))
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'Boda recién apartada',
                'status' => Event::STATUS_RESERVED,
            ]);
    }

    public function test_feed_keeps_existing_calendar_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('calendar.feed'))->assertForbidden();
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function client(): Client
    {
        return Client::create([
            'type' => 'active',
            'full_name' => 'Cliente de prueba',
        ]);
    }

    private function event(Client $client, array $attributes = []): Event
    {
        return Event::create(array_merge([
            'client_id' => $client->id,
            'title' => 'Evento de prueba',
            'event_type' => 'Social',
            'status' => Event::STATUS_CONFIRMED,
            'event_date' => '2026-07-20',
            'start_time' => '18:00:00',
            'end_time' => '23:00:00',
            'total_amount' => 0,
        ], $attributes));
    }
}
