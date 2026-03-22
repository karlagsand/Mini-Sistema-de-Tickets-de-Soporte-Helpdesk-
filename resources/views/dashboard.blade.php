<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                Dashboard Helpdesk
            </h2>
            <span class="text-sm text-slate-500">
                Rol: {{ $user->role->name ?? 'Sin rol' }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-emerald-100 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl">
                    {{ session('success') }}
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

            <div class="bg-white shadow-sm rounded-2xl border border-slate-100 p-6">
                <h3 class="text-lg font-semibold text-slate-800 mb-3">Acciones rápidas</h3>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('tickets.index') }}"
                       class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-800 text-white hover:bg-slate-700 transition">
                        Ver tickets
                    </a>

                    <a href="{{ route('tickets.create') }}"
                       class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-500 transition">
                        Crear ticket
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>