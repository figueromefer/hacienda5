<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GoogleCalendarConnection;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class GoogleCalendarController extends Controller
{
    public function connect(Request $request, GoogleCalendarService $google)
    {
        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        try {
            return redirect()->away($google->authorizationUrl($state));
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', $this->safeMessage($exception, 'No fue posible iniciar la conexión con Google.'));
        }
    }

    public function callback(Request $request, GoogleCalendarService $google)
    {
        if ($request->filled('error')) {
            return redirect()->route('profile.edit')->with('error', 'Google no autorizó el acceso al calendario.');
        }
        if (! $request->filled('code') || ! hash_equals((string) $request->session()->pull('google_oauth_state'), (string) $request->state)) {
            return redirect()->route('profile.edit')->with('error', 'La respuesta de autorización de Google no es válida.');
        }

        try {
            $result = $google->exchangeCode($request->string('code')->toString());
            $token = $result['token'];
            $existing = $request->user()->googleCalendarConnection;
            $connection = GoogleCalendarConnection::updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'google_email' => $result['email'],
                    'access_token' => $token['access_token'],
                    'refresh_token' => $token['refresh_token'] ?? $existing?->refresh_token,
                    'token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
                    'calendar_id' => $existing?->calendar_id ?? 'primary',
                    'calendar_name' => $existing?->calendar_name,
                ]
            );
            $calendars = $google->calendars($connection);
            if ($connection->calendar_name === null && $calendars !== []) {
                $selected = collect($calendars)->firstWhere('id', $connection->calendar_id) ?? $calendars[0];
                $connection->update(['calendar_id' => $selected['id'], 'calendar_name' => $selected['name']]);
            }

            return redirect()->route('profile.edit')->with('success', 'Google Calendar conectado. Selecciona el calendario que deseas usar.');
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('profile.edit')->with('error', $this->safeMessage($exception, 'No fue posible completar la autorización con Google.'));
        }
    }

    public function selectCalendar(Request $request, GoogleCalendarService $google)
    {
        $connection = $request->user()->googleCalendarConnection;
        abort_unless($connection, 404);
        $data = $request->validate(['calendar_id' => ['required', 'string', 'max:255']]);

        try {
            $calendar = collect($google->calendars($connection))->firstWhere('id', $data['calendar_id']);
            if (! $calendar) {
                return back()->with('error', 'El calendario seleccionado no está disponible para escritura.');
            }
            $connection->update(['calendar_id' => $calendar['id'], 'calendar_name' => $calendar['name']]);

            return back()->with('success', 'Calendario seleccionado correctamente.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', $this->safeMessage($exception, 'No fue posible consultar los calendarios de Google.'));
        }
    }

    public function disconnect(Request $request, GoogleCalendarService $google)
    {
        $connection = $request->user()->googleCalendarConnection;
        abort_unless($connection, 404);
        try {
            $google->revoke($connection);
        } catch (Throwable $exception) {
            report($exception);
        }
        $connection->events()->update([
            'google_event_id' => null, 'google_calendar_connection_id' => null,
            'google_synced_at' => null, 'google_sync_status' => null, 'google_sync_error' => null,
        ]);
        $connection->delete();

        return back()->with('success', 'Cuenta de Google Calendar desconectada. Los eventos remotos no fueron eliminados.');
    }

    public function sync(Request $request, Event $event, GoogleCalendarService $google)
    {
        $connection = $request->user()->googleCalendarConnection;
        abort_unless($connection, 404);
        abort_if($event->google_calendar_connection_id && $event->google_calendar_connection_id !== $connection->id, 403);

        try {
            $remoteId = $google->sync($event, $connection);
            $event->update([
                'google_event_id' => $remoteId, 'google_calendar_connection_id' => $connection->id,
                'google_synced_at' => now(), 'google_sync_status' => 'synced', 'google_sync_error' => null,
            ]);

            return back()->with('success', $event->wasChanged('google_event_id') ? 'Evento enviado a Google Calendar.' : 'Evento actualizado en Google Calendar.');
        } catch (Throwable $exception) {
            report($exception);
            $event->update(['google_sync_status' => 'failed', 'google_sync_error' => $this->safeMessage($exception, 'Google Calendar no respondió correctamente.')]);

            return back()->with('error', 'No fue posible sincronizar el evento. Puedes seguir trabajando en Hacienda Cinco.');
        }
    }

    public function unlink(Request $request, Event $event)
    {
        $connection = $request->user()->googleCalendarConnection;
        abort_unless($connection && $event->google_calendar_connection_id === $connection->id, 403);
        $event->update([
            'google_event_id' => null, 'google_calendar_connection_id' => null,
            'google_synced_at' => null, 'google_sync_status' => null, 'google_sync_error' => null,
        ]);

        return back()->with('success', 'Evento desvinculado. El evento de Google se conservó.');
    }

    public function deleteRemote(Request $request, Event $event, GoogleCalendarService $google)
    {
        $connection = $request->user()->googleCalendarConnection;
        abort_unless($connection && $event->google_calendar_connection_id === $connection->id, 403);
        try {
            $google->delete($event, $connection);

            return $this->unlink($request, $event)->with('success', 'Evento eliminado de Google Calendar y desvinculado.');
        } catch (Throwable $exception) {
            report($exception);
            $event->update(['google_sync_status' => 'failed', 'google_sync_error' => $this->safeMessage($exception, 'No fue posible eliminar el evento remoto.')]);

            return back()->with('error', 'No fue posible eliminar el evento de Google Calendar.');
        }
    }

    private function safeMessage(Throwable $exception, string $fallback): string
    {
        $safeMessages = [
            'Google rechazó la autorización.',
            'La autorización de Google expiró. Vuelve a conectar la cuenta.',
            'No fue posible renovar la autorización de Google.',
            'La integración con Google Calendar no está configurada.',
        ];

        return in_array($exception->getMessage(), $safeMessages, true)
            ? Str::limit($exception->getMessage(), 500)
            : $fallback;
    }
}
