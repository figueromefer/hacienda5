<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Event;
use App\Models\GoogleCalendarConnection;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Google\Client as GoogleClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GoogleCalendarIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorization_redirect_and_callback_store_encrypted_tokens(): void
    {
        $user = $this->user();
        $google = Mockery::mock(GoogleCalendarService::class);
        $google->shouldReceive('authorizationUrl')->once()->with(Mockery::type('string'))->andReturn('https://accounts.google.test/oauth');
        $this->app->instance(GoogleCalendarService::class, $google);

        $response = $this->actingAs($user)->get(route('google-calendar.connect'));
        $response->assertRedirect('https://accounts.google.test/oauth');
        $state = session('google_oauth_state');

        $google->shouldReceive('exchangeCode')->once()->with('code-123')->andReturn([
            'token' => ['access_token' => 'access-secret', 'refresh_token' => 'refresh-secret', 'expires_in' => 3600],
            'email' => 'calendar@example.com',
        ]);
        $google->shouldReceive('calendars')->once()->andReturn([['id' => 'team@example.com', 'name' => 'Eventos']]);

        $this->withSession(['google_oauth_state' => $state])->get(route('google-calendar.callback', ['code' => 'code-123', 'state' => $state]))
            ->assertRedirect(route('profile.edit'));

        $connection = $user->fresh()->googleCalendarConnection;
        $this->assertSame('access-secret', $connection->access_token);
        $this->assertSame('refresh-secret', $connection->refresh_token);
        $this->assertSame('team@example.com', $connection->calendar_id);
        $raw = DB::table('google_calendar_connections')->where('id', $connection->id)->first();
        $this->assertNotSame('access-secret', $raw->access_token);
        $this->assertNotSame('refresh-secret', $raw->refresh_token);
    }

    public function test_invalid_oauth_state_is_rejected(): void
    {
        $this->actingAs($this->user())->withSession(['google_oauth_state' => 'expected'])
            ->get(route('google-calendar.callback', ['code' => 'code', 'state' => 'other']))
            ->assertRedirect(route('profile.edit'))->assertSessionHas('error');
    }

    public function test_user_can_select_only_a_calendar_returned_for_their_connection(): void
    {
        $user = $this->user();
        $connection = $this->connection($user);
        $google = Mockery::mock(GoogleCalendarService::class);
        $google->shouldReceive('calendars')->once()->with(Mockery::on(fn ($value) => $value->is($connection)))
            ->andReturn([['id' => 'venue@example.com', 'name' => 'Hacienda']]);
        $this->app->instance(GoogleCalendarService::class, $google);

        $this->actingAs($user)->put(route('google-calendar.calendar'), ['calendar_id' => 'venue@example.com'])
            ->assertSessionHas('success');
        $this->assertSame('Hacienda', $connection->fresh()->calendar_name);
    }

    public function test_expired_access_token_is_refreshed_and_persisted(): void
    {
        $connection = $this->connection($this->user());
        $connection->update(['token_expires_at' => now()->subMinute()]);
        $client = Mockery::mock(GoogleClient::class);
        $client->shouldReceive('setAccessToken')->twice();
        $client->shouldReceive('fetchAccessTokenWithRefreshToken')->once()->with('refresh')
            ->andReturn(['access_token' => 'renewed-access', 'expires_in' => 3600]);
        $service = new class($client) extends GoogleCalendarService
        {
            public function __construct(private GoogleClient $fakeClient) {}

            public function authorize(GoogleCalendarConnection $connection): GoogleClient
            {
                return $this->authorizedClient($connection);
            }

            protected function client(): GoogleClient
            {
                return $this->fakeClient;
            }
        };

        $service->authorize($connection);

        $this->assertSame('renewed-access', $connection->fresh()->access_token);
        $this->assertTrue($connection->fresh()->token_expires_at->isFuture());
    }

    public function test_sync_creates_then_updates_the_same_remote_event_without_duplicate(): void
    {
        $user = $this->user();
        $connection = $this->connection($user);
        $event = $this->event();
        $google = Mockery::mock(GoogleCalendarService::class);
        $google->shouldReceive('sync')->once()->with(Mockery::on(fn ($value) => $value->google_event_id === null), Mockery::type(GoogleCalendarConnection::class))->andReturn('google-123');
        $this->app->instance(GoogleCalendarService::class, $google);

        $this->actingAs($user)->post(route('events.google-calendar.sync', $event))->assertSessionHas('success');
        $this->assertSame('google-123', $event->fresh()->google_event_id);

        $google->shouldReceive('sync')->once()->with(Mockery::on(fn ($value) => $value->google_event_id === 'google-123'), Mockery::type(GoogleCalendarConnection::class))->andReturn('google-123');
        $this->actingAs($user)->post(route('events.google-calendar.sync', $event))->assertSessionHas('success');
        $this->assertSame($connection->id, $event->fresh()->google_calendar_connection_id);
    }

    public function test_google_payload_uses_mexico_timezone_and_real_all_day_logic(): void
    {
        $service = new class extends GoogleCalendarService
        {
            public function eventPayload(Event $event): array
            {
                return $this->payload($event);
            }
        };
        $allDay = $this->event(['start_time' => null, 'end_time' => null]);
        $timed = $this->event(['start_time' => '20:00', 'end_time' => '02:00', 'status' => Event::STATUS_CANCELLED]);

        $allDayPayload = $service->eventPayload($allDay);
        $timedPayload = $service->eventPayload($timed);

        $this->assertSame('2026-08-01', $allDayPayload['start']['date']);
        $this->assertSame('2026-08-02', $allDayPayload['end']['date']);
        $this->assertSame(GoogleCalendarService::TIMEZONE, $timedPayload['start']['timeZone']);
        $this->assertStringContainsString('2026-08-02T02:00:00', $timedPayload['end']['dateTime']);
        $this->assertStringContainsString('Estado: Cancelado', $timedPayload['description']);
    }

    public function test_remote_error_is_recorded_without_breaking_the_application(): void
    {
        $user = $this->user();
        $this->connection($user);
        $event = $this->event();
        $google = Mockery::mock(GoogleCalendarService::class);
        $google->shouldReceive('sync')->andThrow(new \RuntimeException('Autorización expirada'));
        $this->app->instance(GoogleCalendarService::class, $google);

        $this->actingAs($user)->post(route('events.google-calendar.sync', $event))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertSame('failed', $event->fresh()->google_sync_status);
        $this->assertSame('Google Calendar no respondió correctamente.', $event->fresh()->google_sync_error);
    }

    public function test_connection_and_linked_event_are_isolated_by_user(): void
    {
        $owner = $this->user();
        $connection = $this->connection($owner);
        $event = $this->event(['google_event_id' => 'private', 'google_calendar_connection_id' => $connection->id]);
        $other = $this->user();
        $this->connection($other, 'other-token');

        $this->actingAs($other)->post(route('events.google-calendar.sync', $event))->assertForbidden();
        $this->actingAs($other)->delete(route('events.google-calendar.unlink', $event))->assertForbidden();
    }

    public function test_unlink_preserves_remote_and_disconnect_removes_local_tokens(): void
    {
        $user = $this->user();
        $connection = $this->connection($user);
        $event = $this->event(['google_event_id' => 'remote', 'google_calendar_connection_id' => $connection->id]);

        $this->actingAs($user)->delete(route('events.google-calendar.unlink', $event))->assertSessionHas('success');
        $this->assertNull($event->fresh()->google_event_id);

        $google = Mockery::mock(GoogleCalendarService::class);
        $google->shouldReceive('revoke')->once();
        $this->app->instance(GoogleCalendarService::class, $google);
        $this->actingAs($user)->delete(route('google-calendar.disconnect'))->assertSessionHas('success');
        $this->assertDatabaseMissing('google_calendar_connections', ['id' => $connection->id]);
    }

    private function user(): User
    {
        Permission::findOrCreate('manage events');
        $user = User::factory()->create();
        $user->givePermissionTo('manage events');

        return $user;
    }

    private function connection(User $user, string $token = 'access'): GoogleCalendarConnection
    {
        return GoogleCalendarConnection::create([
            'user_id' => $user->id, 'google_email' => $user->email, 'access_token' => $token,
            'refresh_token' => 'refresh', 'token_expires_at' => now()->addHour(),
            'calendar_id' => 'primary', 'calendar_name' => 'Principal',
        ]);
    }

    private function event(array $attributes = []): Event
    {
        $client = Client::create(['type' => 'active', 'full_name' => 'Cliente Google']);

        return Event::create(array_merge([
            'client_id' => $client->id, 'title' => 'Boda', 'event_type' => 'Social',
            'status' => Event::STATUS_CONFIRMED, 'event_date' => '2026-08-01',
            'start_time' => '18:00', 'end_time' => '23:00', 'total_amount' => 0,
        ], $attributes));
    }
}
