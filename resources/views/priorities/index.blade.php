<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Prioridades</h1>
                <p class="page-subtitle">Configuración de niveles de prioridad para los tickets.</p>
            </div>

            <a href="{{ route('priorities.create') }}" class="app-btn-primary">Nueva prioridad</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap space-y-6">
            @if(session('success'))
                <div class="flash-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="flash-error">{{ session('error') }}</div>
            @endif

            <section class="app-table-wrap">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="app-table-head">
                            <tr>
                                <th class="px-6 py-4 text-left">Nombre</th>
                                <th class="px-6 py-4 text-left">Nivel</th>
                                <th class="px-6 py-4 text-left">Color</th>
                                <th class="px-6 py-4 text-left">Registro</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($priorities as $priority)
                                <tr class="app-table-row">
                                    <td class="px-6 py-4 font-semibold text-[var(--text-main)]">{{ $priority->name }}</td>
                                    <td class="px-6 py-4 text-soft">{{ $priority->level }}</td>
                                    <td class="px-6 py-4">
                                        <span class="app-badge badge-blue">{{ $priority->color ?: 'Sin color' }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-soft">{{ $priority->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap justify-end gap-3">
                                            <a href="{{ route('priorities.show', $priority) }}" class="app-btn-secondary">Ver</a>
                                            <a href="{{ route('priorities.edit', $priority) }}" class="app-btn-warning">Editar</a>
                                            <form action="{{ route('priorities.destroy', $priority) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta prioridad?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="app-btn-danger">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="app-table-row">
                                    <td colspan="5" class="px-6 py-10 text-center text-soft">No hay prioridades registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($priorities->hasPages())
                    <div class="border-t border-[var(--border-soft)] px-6 py-4 pagination-wrap">
                        {{ $priorities->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>