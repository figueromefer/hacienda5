<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Generar contrato</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $event->title }}</p>
            </div>
            <a href="{{ route('events.show', $event) }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded">Volver al evento</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('events.contracts.store', $event) }}" class="bg-white shadow rounded p-6 space-y-8">
                @csrf

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 rounded p-4">
                        <ul class="list-disc pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section>
                    <h3 class="text-lg font-semibold mb-4">Datos del arrendatario</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm mb-1">Nombre del arrendatario</label>
                            <input name="arrendatario_nombre" class="w-full border rounded" value="{{ old('arrendatario_nombre', $client?->full_name) }}" required>
                        </div>
                        <div>
                            <label class="block text-sm mb-1">RFC</label>
                            <input name="arrendatario_rfc" class="w-full border rounded" value="{{ old('arrendatario_rfc') }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm mb-1">Domicilio del arrendatario</label>
                            <textarea name="arrendatario_domicilio" rows="3" class="w-full border rounded">{{ old('arrendatario_domicilio') }}</textarea>
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="text-lg font-semibold mb-4">Datos del evento</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div><label class="block text-sm mb-1">Tipo de evento</label><input name="evento_tipo" class="w-full border rounded" value="{{ old('evento_tipo', $event->event_type) }}" required></div>
                        <div><label class="block text-sm mb-1">Fecha del evento</label><input type="date" name="evento_fecha" class="w-full border rounded" value="{{ old('evento_fecha', $event->event_date?->format('Y-m-d')) }}" required></div>
                        <div><label class="block text-sm mb-1">No. personas</label><input name="evento_personas" class="w-full border rounded" value="{{ old('evento_personas', $event->guest_count) }}"></div>
                        <div><label class="block text-sm mb-1">Hora inicio</label><input name="evento_hora_inicio" class="w-full border rounded" value="{{ old('evento_hora_inicio') }}" placeholder="Ej. 8:00 PM"></div>
                        <div><label class="block text-sm mb-1">Hora fin</label><input name="evento_hora_fin" class="w-full border rounded" value="{{ old('evento_hora_fin') }}" placeholder="Ej. 1:00 AM"></div>
                        <div><label class="block text-sm mb-1">Duración</label><input name="evento_duracion" class="w-full border rounded" value="{{ old('evento_duracion', '5 horas') }}"></div>
                        <div class="md:col-span-3"><label class="block text-sm mb-1">Horario de montaje</label><input name="montaje_horario" class="w-full border rounded" value="{{ old('montaje_horario') }}"></div>
                        <div class="md:col-span-3"><label class="block text-sm mb-1">Horario de desmontaje</label><input name="desmontaje_horario" class="w-full border rounded" value="{{ old('desmontaje_horario') }}"></div>
                    </div>
                </section>

                <section>
                    <h3 class="text-lg font-semibold mb-4">Importes</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div><label class="block text-sm mb-1">Renta total</label><input type="number" step="0.01" name="renta_total" class="w-full border rounded" value="{{ old('renta_total', $event->total_amount) }}" required></div>
                        <div><label class="block text-sm mb-1">Anticipo / apartado</label><input type="number" step="0.01" name="anticipo_monto" class="w-full border rounded" value="{{ old('anticipo_monto', $paidIncome) }}"></div>
                        <div><label class="block text-sm mb-1">Segundo pago</label><input type="number" step="0.01" name="segundo_pago_monto" class="w-full border rounded" value="{{ old('segundo_pago_monto') }}"></div>
                        <div><label class="block text-sm mb-1">Saldo restante</label><input type="number" step="0.01" name="saldo_monto" class="w-full border rounded" value="{{ old('saldo_monto', $saldo) }}"></div>
                        <div><label class="block text-sm mb-1">Depósito por daños</label><input type="number" step="0.01" name="deposito_monto" class="w-full border rounded" value="{{ old('deposito_monto', 7000) }}"></div>
                        <div><label class="block text-sm mb-1">Costo hora extra</label><input type="number" step="0.01" name="costo_hora_extra" class="w-full border rounded" value="{{ old('costo_hora_extra', 11000) }}"></div>
                    </div>
                </section>

                <section>
                    <h3 class="text-lg font-semibold mb-4">Firmas y testigos</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2"><label class="block text-sm mb-1">Texto bajo firma del arrendador</label><textarea name="arrendador_firma_nombre" rows="2" class="w-full border rounded">{{ old('arrendador_firma_nombre') }}</textarea></div>
                        <div class="md:col-span-2"><label class="block text-sm mb-1">Nombre bajo firma del arrendatario</label><input name="arrendatario_firma_nombre" class="w-full border rounded" value="{{ old('arrendatario_firma_nombre', $client?->full_name) }}" required></div>
                        <div><label class="block text-sm mb-1">Testigo 1</label><input name="testigo_1_nombre" class="w-full border rounded" value="{{ old('testigo_1_nombre') }}"></div>
                        <div><label class="block text-sm mb-1">Testigo 2</label><input name="testigo_2_nombre" class="w-full border rounded" value="{{ old('testigo_2_nombre') }}"></div>
                    </div>
                </section>

                <section>
                    <h3 class="text-lg font-semibold mb-4">Notas y cláusulas</h3>
                    <div class="space-y-4">
                        <div><label class="block text-sm mb-1">Notas del contrato / paquete / promoción</label><textarea name="notas_contrato" rows="3" class="w-full border rounded">{{ old('notas_contrato') }}</textarea></div>
                        <div><label class="block text-sm mb-1">Cláusulas extras</label><textarea name="clausulas_extra" rows="6" class="w-full border rounded" placeholder="Se insertarán después de la cláusula DÉCIMA SEXTA.">{{ old('clausulas_extra') }}</textarea></div>
                        <div><label class="block text-sm mb-1">Fecha de firma</label><input type="date" name="fecha_firma" class="w-full border rounded" value="{{ old('fecha_firma', now()->format('Y-m-d')) }}"></div>
                    </div>
                </section>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('events.show', $event) }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded">Cancelar</a>
                    <button class="px-4 py-2 bg-black text-white rounded">Generar contrato DOCX</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
