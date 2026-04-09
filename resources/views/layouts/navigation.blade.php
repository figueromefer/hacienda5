<nav x-data="{ open: false }" class="brand-navbar shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ Auth::user()->can('access client portal') && !Auth::user()->can('view dashboard') ? route('client.portal') : route('dashboard') }}" class="flex items-center gap-3">
                        <x-application-logo class="block h-12 w-auto" />
                        <div class="hidden md:block leading-tight">
                            <div class="text-brand-gold text-xs uppercase tracking-[0.35em]">Hacienda Cinco</div>
                            <div class="text-white/90 text-[11px] uppercase tracking-[0.28em]">La Victoria</div>
                        </div>
                    </a>
                </div>

                <div class="hidden space-x-1 sm:-my-px sm:ms-8 sm:flex sm:items-center">
                    @can('view dashboard')
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="brand-nav-link">
                            Dashboard
                        </x-nav-link>
                    @endcan

                    @can('manage users')
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" class="brand-nav-link">
                            Usuarios
                        </x-nav-link>
                    @endcan

                    @can('manage clients')
                        <x-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')" class="brand-nav-link">
                            Clientes
                        </x-nav-link>
                    @endcan

                    @can('manage services')
                        <x-nav-link :href="route('services.index')" :active="request()->routeIs('services.*')" class="brand-nav-link">
                            Servicios
                        </x-nav-link>
                    @endcan

                    @can('manage events')
                        <x-nav-link :href="route('events.index')" :active="request()->routeIs('events.*')" class="brand-nav-link">
                            Eventos
                        </x-nav-link>
                    @endcan

                    @can('manage quotations')
                        <x-nav-link :href="route('quotations.index')" :active="request()->routeIs('quotations.*')" class="brand-nav-link">
                            Cotizaciones
                        </x-nav-link>
                    @endcan

                    @can('manage payments')
                        <x-nav-link :href="route('payments.index')" :active="request()->routeIs('payments.*')" class="brand-nav-link">
                            Pagos
                        </x-nav-link>
                    @endcan

                    @can('manage documents')
                        <x-nav-link :href="route('documents.index')" :active="request()->routeIs('documents.*')" class="brand-nav-link">
                            Documentos
                        </x-nav-link>
                    @endcan

                    @can('view calendar')
                        <x-nav-link :href="route('calendar.index')" :active="request()->routeIs('calendar.*')" class="brand-nav-link">
                            Calendario
                        </x-nav-link>
                    @endcan

                    @can('access client portal')
                        <x-nav-link :href="route('client.portal')" :active="request()->routeIs('client.portal')" class="brand-nav-link">
                            Mi portal
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-brand-gold/40 bg-white/5 text-sm font-medium text-white hover:bg-white/10 hover:text-brand-gold focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 border-b border-brand-gold/10">
                            <div class="text-sm font-semibold text-brand-green">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-brand-gray/80">{{ Auth::user()->email }}</div>
                        </div>

                        <x-dropdown-link :href="route('logout.get')">
                            Cerrar sesión
                        </x-dropdown-link>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-white/80 hover:text-brand-gold hover:bg-white/10 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-brand-gold/15 bg-brand-green">
        <div class="pt-2 pb-3 space-y-1">
            @can('view dashboard')
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    Dashboard
                </x-responsive-nav-link>
            @endcan

            @can('manage users')
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    Usuarios
                </x-responsive-nav-link>
            @endcan

            @can('manage clients')
                <x-responsive-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')">
                    Clientes
                </x-responsive-nav-link>
            @endcan

            @can('manage services')
                <x-responsive-nav-link :href="route('services.index')" :active="request()->routeIs('services.*')">
                    Servicios
                </x-responsive-nav-link>
            @endcan

            @can('manage events')
                <x-responsive-nav-link :href="route('events.index')" :active="request()->routeIs('events.*')">
                    Eventos
                </x-responsive-nav-link>
            @endcan

            @can('manage quotations')
                <x-responsive-nav-link :href="route('quotations.index')" :active="request()->routeIs('quotations.*')">
                    Cotizaciones
                </x-responsive-nav-link>
            @endcan

            @can('manage payments')
                <x-responsive-nav-link :href="route('payments.index')" :active="request()->routeIs('payments.*')">
                    Pagos
                </x-responsive-nav-link>
            @endcan

            @can('manage documents')
                <x-responsive-nav-link :href="route('documents.index')" :active="request()->routeIs('documents.*')">
                    Documentos
                </x-responsive-nav-link>
            @endcan

            @can('view calendar')
                <x-responsive-nav-link :href="route('calendar.index')" :active="request()->routeIs('calendar.*')">
                    Calendario
                </x-responsive-nav-link>
            @endcan

            @can('access client portal')
                <x-responsive-nav-link :href="route('client.portal')" :active="request()->routeIs('client.portal')">
                    Mi portal
                </x-responsive-nav-link>
            @endcan
        </div>

        <div class="pt-4 pb-4 border-t border-brand-gold/15">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-white/70">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('logout.get')">
                    Cerrar sesión
                </x-responsive-nav-link>
            </div>
        </div>
    </div>
</nav>