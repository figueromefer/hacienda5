@php
    $toasts = collect([
        session('success') ? ['type' => 'success', 'message' => session('success')] : null,
        session('error') ? ['type' => 'error', 'message' => session('error')] : null,
        session('warning') ? ['type' => 'warning', 'message' => session('warning')] : null,
        session('status') ? ['type' => 'success', 'message' => session('status')] : null,
    ])->filter()->values();
@endphp

@if($toasts->isNotEmpty())
    <div class="fixed right-4 top-24 z-50 w-full max-w-sm space-y-3">
        @foreach($toasts as $toast)
            <div
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 4500)"
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-6 scale-95"
                x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                x-transition:leave-end="opacity-0 translate-x-6 scale-95"
                class="flex items-start gap-3 rounded-2xl border bg-white p-4 shadow-xl {{ $toast['type'] === 'success' ? 'border-green-200' : ($toast['type'] === 'error' ? 'border-red-200' : 'border-amber-200') }}"
            >
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $toast['type'] === 'success' ? 'bg-green-100 text-green-700' : ($toast['type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                    @if($toast['type'] === 'success')
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    @elseif($toast['type'] === 'error')
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    @else
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12V16.5zm9-4.5a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900">
                        {{ $toast['type'] === 'success' ? 'Listo' : ($toast['type'] === 'error' ? 'Error' : 'Atención') }}
                    </p>
                    <p class="mt-0.5 text-sm text-gray-600">{{ $toast['message'] }}</p>
                </div>

                <button type="button" @click="show = false" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        @endforeach
    </div>
@endif
