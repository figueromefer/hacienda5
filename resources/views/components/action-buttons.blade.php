@props([
    'show' => null,
    'download' => null,
    'edit' => null,
    'delete' => null,
    'confirm' => 'Esta acción no se puede deshacer. Para confirmar, escribe ELIMINAR.',
    'confirmationWord' => 'ELIMINAR',
])

<div x-data="{ actionsOpen: false, confirmDelete: false, typedConfirmation: '' }" class="relative inline-block text-left">
    <button type="button" @click="actionsOpen = ! actionsOpen" :aria-expanded="actionsOpen.toString()" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-gold">
        Acciones
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
    </button>

    <div x-cloak x-show="actionsOpen" x-transition @click.outside="actionsOpen = false" class="absolute right-0 z-30 mt-2 w-44 overflow-hidden rounded-xl border bg-white py-1 shadow-xl">
    @if($show)
        <a href="{{ $show }}" class="flex min-h-11 items-center gap-3 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>Ver</span>
        </a>
    @endif

    @if($download)
        <a href="{{ $download }}" class="flex min-h-11 items-center gap-3 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 10.5L12 15m0 0l4.5-4.5M12 15V3" />
            </svg>
            <span>Descargar PDF</span>
        </a>
    @endif

    @if($edit)
        <a href="{{ $edit }}" class="flex min-h-11 items-center gap-3 px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125L16.875 4.5" />
            </svg>
            <span>Editar</span>
        </a>
    @endif

    @if($delete)
        <button type="button" @click="actionsOpen = false; confirmDelete = true; typedConfirmation = ''" class="flex min-h-11 w-full items-center gap-3 px-4 py-2 text-left text-sm font-medium text-red-700 hover:bg-red-50">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M18.16 19.673A2.25 2.25 0 0115.916 21H8.084a2.25 2.25 0 01-2.244-1.327L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .563c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
            </svg>
            <span>Eliminar</span>
        </button>

    @endif
    </div>

    @if($delete)
        <div x-cloak x-show="confirmDelete" x-transition.opacity @keydown.escape.window="confirmDelete = false" class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-black/50 px-4 py-6">
            <div @click.outside="confirmDelete = false" x-transition.scale class="my-auto w-full max-w-md rounded-2xl bg-white p-4 sm:p-6 shadow-2xl">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12V16.5zm9-4.5a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Confirmar eliminación</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ $confirm }}</p>
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-red-100 bg-red-50 p-4">
                    <label class="block text-sm font-semibold text-red-800">
                        Escribe <span class="font-black">{{ $confirmationWord }}</span> para continuar
                    </label>
                    <input
                        type="text"
                        x-model="typedConfirmation"
                        class="mt-2 w-full rounded-lg border-red-200 focus:border-red-500 focus:ring-red-500"
                        autocomplete="off"
                        placeholder="{{ $confirmationWord }}"
                    >
                </div>

                <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <button type="button" @click="confirmDelete = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Cancelar</button>
                    <form action="{{ $delete }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            :disabled="typedConfirmation !== '{{ $confirmationWord }}'"
                            :class="typedConfirmation === '{{ $confirmationWord }}' ? 'bg-red-600 hover:bg-red-700 cursor-pointer' : 'bg-red-300 cursor-not-allowed'"
                            class="danger-submit w-full rounded-lg px-4 py-2 text-sm font-semibold text-white transition sm:w-auto"
                        >
                            Sí, eliminar definitivamente
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
