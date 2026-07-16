<nav x-data="{ open: false, catalogsOpen: false }" @keydown.escape.window="open = false; catalogsOpen = false" class="brand-navbar relative z-40 shadow-sm">
    @php
        $showClientPortal = Auth::user()->can('access client portal') && ! Auth::user()->can('view dashboard');

        $navigationItems = collect([
            ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'permission' => 'view dashboard'],
            ['label' => 'Proveedores', 'route' => 'suppliers.index', 'active' => 'suppliers.*', 'permission' => 'manage suppliers'],
            ['label' => 'Eventos', 'route' => 'events.index', 'active' => 'events.*', 'permission' => 'manage events'],
            ['label' => 'Cotizaciones', 'route' => 'quotations.index', 'active' => 'quotations.*', 'permission' => 'manage quotations'],
            ['label' => 'Cuentas por pagar', 'route' => 'supplier-payables.index', 'active' => 'supplier-payables.*', 'permission' => 'manage payments'],
            ['label' => 'Movimientos', 'route' => 'transactions.index', 'active' => 'transactions.*', 'permission' => 'manage payments'],
            ['label' => 'Calendario', 'route' => 'calendar.index', 'active' => 'calendar.*', 'permission' => 'view calendar'],
        ])->filter(fn (array $item) => Auth::user()->can($item['permission']));

        $catalogItems = collect([
            ['label' => 'Usuarios', 'route' => 'users.index', 'active' => 'users.*', 'permission' => 'manage users'],
            ['label' => 'Clientes', 'route' => 'clients.index', 'active' => 'clients.*', 'permission' => 'manage clients'],
            ['label' => 'Servicios', 'route' => 'services.index', 'active' => 'services.*', 'permission' => 'manage services'],
            ['label' => 'Conceptos de gasto', 'route' => 'expense-concepts.index', 'active' => 'expense-concepts.*', 'permission' => 'manage expense concepts'],
        ])->filter(fn (array $item) => Auth::user()->can($item['permission']));

        if ($showClientPortal) {
            $navigationItems->push([
                'label' => 'Mi portal',
                'route' => 'client.portal',
                'active' => 'client.portal',
            ]);
        }
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex min-w-0">
                <div class="shrink-0 flex items-center">
                    <a href="{{ $showClientPortal ? route('client.portal') : route('dashboard') }}" class="flex items-center gap-3" aria-label="Ir al inicio">
                        <x-application-logo class="block h-12 w-auto" />
                        <div class="hidden md:block leading-tight">
                            <div class="text-brand-gold text-xs uppercase tracking-[0.35em]">Hacienda Cinco</div>
                            <div class="text-white/90 text-[11px] uppercase tracking-[0.28em]">La Victoria</div>
                        </div>
                    </a>
                </div>

                <div class="hidden space-x-1 xl:-my-px xl:ms-8 xl:flex xl:items-center">
                    @foreach($navigationItems as $item)
                        <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['active'])" class="brand-nav-link">
                            {{ $item['label'] }}
                        </x-nav-link>
                    @endforeach

                    @if($catalogItems->isNotEmpty())
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button type="button" class="brand-nav-link inline-flex h-full items-center gap-1 px-3 text-sm font-medium {{ request()->routeIs($catalogItems->pluck('active')->all()) ? 'text-brand-gold' : 'text-white/90' }}" aria-label="Abrir menú Catálogos">
                                    Catálogos
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                @foreach($catalogItems as $item)
                                    <x-dropdown-link :href="route($item['route'])">{{ $item['label'] }}</x-dropdown-link>
                                @endforeach
                            </x-slot>
                        </x-dropdown>
                    @endif
                </div>
            </div>

            <div class="hidden xl:flex xl:items-center xl:ms-6">
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button type="button" class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-full bg-transparent text-white hover:bg-white/10 hover:text-brand-gold focus:outline-none focus:ring-2 focus:ring-brand-gold transition" aria-label="Abrir menú de usuario">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 1115 0" />
                            </svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="px-4 py-3 border-b">
                            <div class="text-sm font-semibold">{{ Auth::user()->name }}</div>
                            <div class="text-xs break-all">{{ Auth::user()->email }}</div>
                        </div>
                        <x-dropdown-link :href="route('profile.edit')">Editar perfil</x-dropdown-link>
                        <x-dropdown-link :href="route('logout.get')">Cerrar sesión</x-dropdown-link>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center xl:hidden">
                <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="mobile-navigation" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-md text-white/90 hover:text-brand-gold hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-brand-gold transition">
                    <span class="sr-only" x-text="open ? 'Cerrar menú principal' : 'Abrir menú principal'">Abrir menú principal</span>
                    <svg class="h-7 w-7" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path x-show="! open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div id="mobile-navigation" x-show="open" x-cloak x-transition @click.outside="open = false" class="mobile-navigation-panel xl:hidden border-t border-brand-gold/20 bg-brand-green">
        <div class="py-2 space-y-1">
            @foreach($navigationItems as $item)
                <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['active'])">
                    {{ $item['label'] }}
                </x-responsive-nav-link>
            @endforeach

            @if($catalogItems->isNotEmpty())
                <button type="button" @click="catalogsOpen = ! catalogsOpen" :aria-expanded="catalogsOpen.toString()" aria-controls="mobile-catalogs" class="flex w-full items-center justify-between border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-white/80 hover:border-brand-gold hover:bg-white/10 hover:text-white">
                    <span>Catálogos</span>
                    <svg class="h-4 w-4 transition" :class="{ 'rotate-180': catalogsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                    </svg>
                </button>
                <div id="mobile-catalogs" x-show="catalogsOpen" x-cloak class="bg-black/10 py-1">
                    @foreach($catalogItems as $item)
                        <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['active'])" class="ps-8">
                            {{ $item['label'] }}
                        </x-responsive-nav-link>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="py-4 border-t border-brand-gold/20">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-white/70 break-all">{{ Auth::user()->email }}</div>
            </div>
            <div class="mt-3">
                <x-responsive-nav-link :href="route('profile.edit')">Editar perfil</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('logout.get')">Cerrar sesión</x-responsive-nav-link>
            </div>
        </div>
    </div>
</nav>
