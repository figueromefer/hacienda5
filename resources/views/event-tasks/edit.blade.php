<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Editar tarea</h2>
            <p class="mt-1 text-sm text-gray-500">{{ $eventTask->event->title }}</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('event-tasks.update', $eventTask) }}" class="space-y-5 rounded-xl bg-white p-5 shadow sm:p-6">
                @csrf
                @method('PUT')
                @if($returnToDashboard)
                    <input type="hidden" name="origin" value="dashboard">
                @endif

                <div>
                    <label for="title" class="block text-sm font-medium">Título o descripción</label>
                    <input id="title" name="title" value="{{ old('title', $eventTask->title) }}" required maxlength="255" class="mt-1 w-full rounded border-gray-300">
                    @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="assigned_to" class="block text-sm font-medium">Responsable</label>
                    <select id="assigned_to" name="assigned_to" class="mt-1 w-full rounded border-gray-300" @disabled(! $canReassign)>
                        <option value="">Sin asignar</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) old('assigned_to', $eventTask->assigned_to) === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @unless($canReassign)
                        <input type="hidden" name="assigned_to" value="{{ $eventTask->assigned_to }}">
                        <p class="mt-1 text-xs text-gray-500">Solo un usuario con permiso para administrar eventos puede reasignar la tarea.</p>
                    @endunless
                    @error('assigned_to')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="due_date" class="block text-sm font-medium">Fecha límite</label>
                    <input id="due_date" type="datetime-local" name="due_date" value="{{ old('due_date', $eventTask->due_date?->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded border-gray-300">
                    @error('due_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium">Estatus</label>
                    <select id="status" name="status" class="mt-1 w-full rounded border-gray-300">
                        @foreach(\App\Models\EventTask::STATUS_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $eventTask->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium">Notas</label>
                    <textarea id="notes" name="notes" rows="4" class="mt-1 w-full rounded border-gray-300">{{ old('notes', $eventTask->notes) }}</textarea>
                    @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ $returnToDashboard ? route('dashboard') : route('events.show', $eventTask->event_id) }}" class="inline-flex min-h-11 items-center justify-center rounded bg-gray-200 px-4 py-2">Cancelar</a>
                    <button class="min-h-11 rounded bg-black px-4 py-2 font-semibold text-white">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
