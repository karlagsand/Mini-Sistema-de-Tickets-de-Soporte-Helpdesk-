<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Categorías</h1>
                <p class="page-subtitle">Administración de categorías para clasificación de tickets.</p>
            </div>

            <a href="{{ route('categories.create') }}" class="app-btn-primary">
                Nueva categoría
            </a>
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
                                <th class="px-6 py-4 text-left">Descripción</th>
                                <th class="px-6 py-4 text-left">Estado</th>
                                <th class="px-6 py-4 text-left">Registro</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                <tr class="app-table-row">
                                    <td class="px-6 py-4 font-semibold text-[var(--text-main)]">{{ $category->name }}</td>
                                    <td class="px-6 py-4 text-soft">{{ $category->description ?: 'Sin descripción' }}</td>
                                    <td class="px-6 py-4">
                                        <span class="app-badge {{ $category->is_active ? 'badge-emerald' : 'badge-slate' }}">
                                            {{ $category->is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-soft">{{ $category->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap justify-end gap-3">
                                            <a href="{{ route('categories.show', $category) }}" class="app-btn-secondary">Ver</a>
                                            <a href="{{ route('categories.edit', $category) }}" class="app-btn-warning">Editar</a>
                                            <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="app-btn-danger">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="app-table-row">
                                    <td colspan="5" class="px-6 py-10 text-center text-soft">No hay categorías registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($categories->hasPages())
                    <div class="border-t border-[var(--border-soft)] px-6 py-4 pagination-wrap">
                        {{ $categories->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>