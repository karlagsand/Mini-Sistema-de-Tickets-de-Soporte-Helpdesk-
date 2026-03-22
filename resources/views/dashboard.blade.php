<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-slate-800 leading-tight">
                    Dashboard Helpdesk
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Panel principal del sistema de tickets
                </p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                Rol: {{ $user->role->name ?? 'Sin rol' }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-emerald-100 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-2xl">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                <div class="bg-white shadow-sm rounded-2xl p-6 border border-slate-100">
                    <p class="text-sm text-slate-500">Total de tickets</p>
                    <p class="text-3xl font-bold text-slate-800 mt-2">{{ $stats['total'] }}</p>
                </div>

                <div class="bg-white shadow-sm rounded-2xl p-6 border border-slate-100">
                    <p class="text-sm text-slate-500">Nuevos</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2">{{ $stats['new'] }}</p>
                </div>

                <div class="bg-white shadow-sm rounded-2xl p-6 border border-slate-100">
                    <p class="text-sm text-slate-500">En proceso</p>
                    <p class="text-3xl font-bold text-amber-500 mt-2">{{ $stats['in_progress'] }}</p>
                </div>

                <div class="bg-white shadow-sm rounded-2xl p-6 border border-slate-100">
                    <p class="text-sm text-slate-500">Cerrados</p>
                    <p class="text-3xl font-bold text-emerald-600 mt-2">{{ $stats['closed'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white shadow-sm rounded-2xl border border-slate-100 p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Acciones rápidas</h3>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('tickets.index') }}"
                           class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-800 text-white hover:bg-slate-700 transition">
                            Ver tickets
                        </a>

                        <a href="{{ route('tickets.create') }}"
                           class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-500 transition">
                            Crear ticket
                        </a>

                        @if($user->isAdmin())
                            <a href="{{ route('users.index') }}"
                               class="inline-flex items-center px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-500 transition">
                                Gestionar usuarios
                            </a>

                            <a href="{{ route('categories.index') }}"
                               class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-200 text-slate-800 hover:bg-slate-300 transition">
                                Categorías
                            </a>

                            <a href="{{ route('priorities.index') }}"
                               class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-200 text-slate-800 hover:bg-slate-300 transition">
                                Prioridades
                            </a>

                            <a href="{{ route('ticket-statuses.index') }}"
                               class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-200 text-slate-800 hover:bg-slate-300 transition">
                                Estados
                            </a>
                        @endif
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-2xl border border-slate-100 p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Resumen del usuario</h3>

                    <div class="space-y-3 text-sm text-slate-600">
                        <p><span class="font-medium text-slate-800">Nombre:</span> {{ $user->name }}</p>
                        <p><span class="font-medium text-slate-800">Correo:</span> {{ $user->email }}</p>
                        <p><span class="font-medium text-slate-800">Rol:</span> {{ $user->role->name ?? 'Sin rol' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>