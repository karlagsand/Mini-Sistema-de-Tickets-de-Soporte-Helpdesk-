<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Detalle de prioridad</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-4">
                <p><span class="font-semibold">Nombre:</span> {{ $priority->name }}</p>
                <p><span class="font-semibold">Nivel:</span> {{ $priority->level }}</p>
                <p><span class="font-semibold">Color:</span> {{ $priority->color ?? 'Sin color' }}</p>
                <p><span class="font-semibold">Creada:</span> {{ $priority->created_at?->format('d/m/Y H:i') }}</p>

                <div class="pt-4">
                    <a href="{{ route('priorities.index') }}"
                       class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 transition">
                        Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>