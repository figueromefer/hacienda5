<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Calendario general</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded p-3 sm:p-6 overflow-x-auto">
                <div class="mb-5 flex flex-wrap gap-x-4 gap-y-2 text-sm" aria-label="Leyenda de estatus">
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full" style="background:#b45309"></span>Apartado</span>
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full" style="background:#2563eb"></span>Por confirmar</span>
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full" style="background:#15803d"></span>Confirmado</span>
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full" style="background:#0f766e"></span>Completado</span>
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full" style="background:#6b7280"></span>Cancelado</span>
                </div>
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales-all.global.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const calendarEl = document.getElementById('calendar');
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    locale: 'es',
                    timeZone: 'America/Mexico_City',
                    initialView: 'dayGridMonth',
                    firstDay: 1,
                    nowIndicator: true,
                    displayEventEnd: true,
                    eventTimeFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    },
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,listMonth'
                    },
                    buttonText: {
                        today: 'Hoy',
                        month: 'Mes',
                        week: 'Semana',
                        list: 'Lista'
                    },
                    events: {
                        url: '{{ route('calendar.feed') }}',
                        method: 'GET',
                        failure: function () {
                            calendarEl.insertAdjacentHTML('afterbegin', '<div class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">No fue posible cargar los eventos. Recarga la página o inténtalo nuevamente.</div>');
                        }
                    },
                    eventDidMount: function (info) {
                        const props = info.event.extendedProps;
                        info.el.title = [
                            info.event.title,
                            props.client,
                            props.eventType,
                            props.statusLabel
                        ].filter(Boolean).join(' · ');
                    }
                });
                calendar.render();
            });
        </script>
    @endpush
</x-app-layout>
