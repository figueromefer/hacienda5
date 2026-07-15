<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Google Calendar</h2>
        <p class="mt-1 text-sm text-gray-600">Conecta tu cuenta y elige un calendario con permiso de escritura.</p>
    </header>

    @if($googleConnection)
        <div class="mt-6 space-y-4">
            <div class="rounded-lg border bg-green-50 p-4 text-sm">
                <div><strong>Estado:</strong> Conectado</div>
                <div><strong>Cuenta:</strong> {{ $googleConnection->google_email ?? 'Cuenta de Google' }}</div>
                <div><strong>Calendario:</strong> {{ $googleConnection->calendar_name ?? $googleConnection->calendar_id }}</div>
            </div>

            @if($googleCalendarError)
                <p class="text-sm text-red-600">{{ $googleCalendarError }}</p>
            @elseif($googleCalendars)
                <form method="POST" action="{{ route('google-calendar.calendar') }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <label for="calendar_id" class="block text-sm font-medium text-gray-700">Calendario de destino</label>
                    <select id="calendar_id" name="calendar_id" class="block w-full rounded-md border-gray-300">
                        @foreach($googleCalendars as $calendar)
                            <option value="{{ $calendar['id'] }}" @selected($googleConnection->calendar_id === $calendar['id'])>{{ $calendar['name'] }}</option>
                        @endforeach
                    </select>
                    <x-primary-button>Guardar calendario</x-primary-button>
                </form>
            @endif

            <form method="POST" action="{{ route('google-calendar.disconnect') }}" onsubmit="return confirm('¿Desconectar Google Calendar? Los eventos remotos se conservarán.')">
                @csrf
                @method('DELETE')
                <x-danger-button>Desconectar cuenta</x-danger-button>
            </form>
        </div>
    @else
        <div class="mt-6">
            <a href="{{ route('google-calendar.connect') }}" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-blue-500">Conectar Google Calendar</a>
        </div>
    @endif
</section>
