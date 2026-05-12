<nav x-data="{ open: false }" class="brand-navbar shadow-sm">
    @php
        $showClientPortal = Auth::user()->can('access client portal') && ! Auth::user()->can('view dashboard');
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ $showClientPortal ? route('client.portal') : route('dashboard') }}" class="flex items-center gap-3">
                        <x-application-logo class="block h-12 w-auto" />
                        <div class="hidden md:block leading-tight">
                            <div class="text-brand-gold text-xs uppercase tracking-[0.35em]">Hacienda Cinco</div>
                            <div class="text-white/90 text-[11px] uppercase tracking-[0.28em]">La Victoria</div>
                        </div>
                    </a>
                </div>

                <div class="hidden space-x-1 sm:-my-px sm:ms-8 sm:flex sm:items-center">
                    @can('view dashboard')
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="brand-nav-link">Dashboard</x-nav-link>
                    @endcan

                    @can('manage users')
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" class="brand-nav-link">Usuarios</x-nav-link>
                    @endcan

                    @can('manage clients')
                        <x-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')" class="brand-nav-link">Clientes</x-nav-link>
                    @endcan

                    @can('manage services')
                        <x-nav-link :href="route('services.index')" :active="request()->routeIs('services.*')" class="brand-nav-link">Servicios</x-nav-link>
                    @endcan

                    @can('manage events')
                        <x-nav-link :href="route('events.index')" :active="request()->routeIs('events.*')" class="brand-nav-link">Eventos</x-nav-link>
                    @endcan

                    @can('manage quotations')
                        <x-nav-link :href="route('quotations.index')" :active="request()->routeIs('quotations.*')" class="brand-nav-link">Cotizaciones</x-nav-link>
                    @endcan

                    @can('manage payments')
                        <x-nav-link :href="route('transactions.index')" :active="request()->routeIs('transactions.*')" class="brand-nav-link">Movimientos</x-nav-link>
                    @endcan

                    @can('view calendar')
                        <x-nav-link :href="route('calendar.index')" :active="request()->routeIs('calendar.*')" class="brand-nav-link">Calendario</x-nav-link>
                    @endcan

                    @if($showClientPortal)
                        <x-nav-link :href="route('client.portal')" :active="request()->routeIs('client.portal')" class="brand-nav-link">Mi portal</x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-full bg-transparent text-white hover:bg-white/10 hover:text-brand-gold focus:outline-none transition">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 1115 0" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 border-b">
                            <div class="text-sm font-semibold">{{ Auth::user()->name }}</div>
                            <div class="text-xs">{{ Auth::user()->email }}</div>
                        </div>

                        <x-dropdown-link :href="route('logout.get')">Cerrar sesión</x-dropdown-link>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>