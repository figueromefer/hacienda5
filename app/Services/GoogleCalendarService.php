<?php

namespace App\Services;

use App\Models\Event;
use App\Models\GoogleCalendarConnection;
use Carbon\CarbonImmutable;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Oauth2;
use RuntimeException;

class GoogleCalendarService
{
    public const TIMEZONE = 'America/Mexico_City';

    public function authorizationUrl(string $state): string
    {
        $client = $this->client();
        $client->setState($state);

        return $client->createAuthUrl();
    }

    public function exchangeCode(string $code): array
    {
        $client = $this->client();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            throw new RuntimeException('Google rechazó la autorización.');
        }
        $client->setAccessToken($token);
        $profile = (new Oauth2($client))->userinfo->get();

        return ['token' => $token, 'email' => $profile->getEmail()];
    }

    public function calendars(GoogleCalendarConnection $connection): array
    {
        $service = $this->calendar($connection);
        $items = [];
        $pageToken = null;
        do {
            $list = $service->calendarList->listCalendarList(['pageToken' => $pageToken]);
            foreach ($list->getItems() as $calendar) {
                if ($calendar->getAccessRole() === 'owner' || $calendar->getAccessRole() === 'writer') {
                    $items[] = ['id' => $calendar->getId(), 'name' => $calendar->getSummary()];
                }
            }
            $pageToken = $list->getNextPageToken();
        } while ($pageToken);

        return $items;
    }

    public function sync(Event $event, GoogleCalendarConnection $connection): string
    {
        $service = $this->calendar($connection);
        $payload = new GoogleEvent($this->payload($event));
        if ($event->google_event_id) {
            $remote = $service->events->update($connection->calendar_id, $event->google_event_id, $payload);
        } else {
            $remote = $service->events->insert($connection->calendar_id, $payload);
        }

        return $remote->getId();
    }

    public function delete(Event $event, GoogleCalendarConnection $connection): void
    {
        $this->calendar($connection)->events->delete($connection->calendar_id, $event->google_event_id);
    }

    public function revoke(GoogleCalendarConnection $connection): void
    {
        $client = $this->authorizedClient($connection);
        $client->revokeToken();
    }

    protected function payload(Event $event): array
    {
        $description = "Tipo: {$event->event_type}\nEstado: {$event->status_label}";
        if ($event->notes) {
            $description .= "\n\n{$event->notes}";
        }
        $payload = ['summary' => $event->title, 'description' => $description];
        if ($event->start_time) {
            $start = CarbonImmutable::parse($event->event_date->format('Y-m-d').' '.$event->start_time, self::TIMEZONE);
            $end = $event->end_time
                ? CarbonImmutable::parse($event->event_date->format('Y-m-d').' '.$event->end_time, self::TIMEZONE)
                : $start->addHour();
            if ($end->lessThanOrEqualTo($start)) {
                $end = $end->addDay();
            }
            $payload['start'] = ['dateTime' => $start->toRfc3339String(), 'timeZone' => self::TIMEZONE];
            $payload['end'] = ['dateTime' => $end->toRfc3339String(), 'timeZone' => self::TIMEZONE];
        } else {
            $payload['start'] = ['date' => $event->event_date->format('Y-m-d')];
            $payload['end'] = ['date' => $event->event_date->addDay()->format('Y-m-d')];
        }

        return $payload;
    }

    protected function calendar(GoogleCalendarConnection $connection): Calendar
    {
        return new Calendar($this->authorizedClient($connection));
    }

    protected function authorizedClient(GoogleCalendarConnection $connection): Client
    {
        $client = $this->client();
        $client->setAccessToken([
            'access_token' => $connection->access_token,
            'refresh_token' => $connection->refresh_token,
            'expires_in' => max(0, now()->diffInSeconds($connection->token_expires_at, false)),
            'created' => now()->timestamp,
        ]);
        if ($connection->token_expires_at?->isPast()) {
            if (! $connection->refresh_token) {
                throw new RuntimeException('La autorización de Google expiró. Vuelve a conectar la cuenta.');
            }
            $token = $client->fetchAccessTokenWithRefreshToken($connection->refresh_token);
            if (isset($token['error'])) {
                throw new RuntimeException('No fue posible renovar la autorización de Google.');
            }
            $connection->update([
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? $connection->refresh_token,
                'token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
            ]);
            $client->setAccessToken($token + ['refresh_token' => $connection->refresh_token]);
        }

        return $client;
    }

    protected function client(): Client
    {
        foreach (['client_id', 'client_secret', 'redirect'] as $key) {
            if (! config("services.google.$key")) {
                throw new RuntimeException('La integración con Google Calendar no está configurada.');
            }
        }
        $client = new Client;
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);
        $client->setScopes([Calendar::CALENDAR, Oauth2::USERINFO_EMAIL]);

        return $client;
    }
}
