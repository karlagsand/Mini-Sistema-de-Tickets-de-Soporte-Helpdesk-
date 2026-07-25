<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-[var(--border-soft)] bg-[var(--bg-header)] backdrop-blur-md shadow-sm">
    @php
        $navUser = auth()->user();
        $isRequester = $navUser?->isUserRole();
        $ticketsLabel = $isRequester ? 'Mis solicitudes' : ($navUser?->isAgent() ? 'Bandeja' : 'Tickets');
        $dashboardLabel = $isRequester ? 'Inicio' : 'Dashboard';
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <x-application-logo class="block h-11 w-auto" />
                        <span class="hidden sm:block text-lg font-bold tracking-wide text-[var(--text-main)]">
                            Helpdesk
                        </span>
                    </a>
                </div>

                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    @auth
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ $dashboardLabel }}
                        </x-nav-link>

                        <x-nav-link :href="route('tickets.index')" :active="request()->routeIs('tickets.*')">
                            {{ $ticketsLabel }}
                        </x-nav-link>

                        @if(auth()->user()->isAdmin())
                            <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                                Usuarios
                            </x-nav-link>

                            <x-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.*')">
                                Categorías
                            </x-nav-link>

                            <x-nav-link :href="route('priorities.index')" :active="request()->routeIs('priorities.*')">
                                Prioridades
                            </x-nav-link>

                            <x-nav-link :href="route('ticket-statuses.index')" :active="request()->routeIs('ticket-statuses.*')">
                                Estados
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <div class="flex items-center gap-3">
                    <a href="{{ route('profile.edit') }}"
                       class="inline-flex items-center px-4 py-2 rounded-2xl text-sm font-medium text-[var(--text-main)] bg-white border border-[var(--border-soft)] hover:bg-slate-50 transition">
                        {{ Auth::user()->name }}
                        <span class="ms-2 text-xs text-[var(--text-soft)]">
                            ({{ Auth::user()->role->name ?? 'Sin rol' }})
                        </span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="logout-btn">
                            <svg class="logout-btn-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3-3H9m0 0l3-3m-3 3l3 3" />
                            </svg>
                            <span>Cerrar sesión</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-[var(--text-soft)] hover:text-[var(--text-main)] hover:bg-white transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="sr-only">Abrir menú</span>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-[var(--border-soft)] bg-white">
        <div class="pt-2 pb-3 space-y-1">
            @auth
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ $dashboardLabel }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('tickets.index')" :active="request()->routeIs('tickets.*')">
                    {{ $ticketsLabel }}
                </x-responsive-nav-link>

                @if(auth()->user()->isAdmin())
                    <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                        Usuarios
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.*')">
                        Categorías
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('priorities.index')" :active="request()->routeIs('priorities.*')">
                        Prioridades
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('ticket-statuses.index')" :active="request()->routeIs('ticket-statuses.*')">
                        Estados
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        <div class="pt-4 pb-4 border-t border-[var(--border-soft)]">
            <div class="px-4">
                <div class="font-medium text-base text-[var(--text-main)]">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-[var(--text-soft)]">{{ Auth::user()->email }}</div>
                <div class="font-medium text-xs text-[var(--text-muted)] mt-1">{{ Auth::user()->role->name ?? 'Sin rol' }}</div>
            </div>

            <div class="mt-3 space-y-2 px-4">
                <a href="{{ route('profile.edit') }}" class="app-btn-secondary w-full">
                    Perfil
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn w-full">
                        <svg class="logout-btn-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3-3H9m0 0l3-3m-3 3l3 3" />
                        </svg>
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
