<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Estados de ticket</h1>
                <p class="page-subtitle">Configuración de estados para el flujo de atención de tickets.</p>
            </div>

            <a href="{{ route('ticket-statuses.create') }}" class="app-btn-primary">Nuevo estado</a>
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
                                <th class="px-6 py-4 text-left">Slug</th>
                                <th class="px-6 py-4 text-left">Orden</th>
                                <th class="px-6 py-4 text-left">Cierre</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($statuses as $status)
                                <tr class="app-table-row">
                                    <td class="px-6 py-4 font-semibold text-[var(--text-main)]">{{ $status->name }}</td>
                                    <td class="px-6 py-4 text-soft">{{ $status->slug }}</td>
                                    <td class="px-6 py-4 text-soft">{{ $status->sort_order }}</td>
                                    <td class="px-6 py-4">
                                        <span class="app-badge {{ $status->is_closed ? 'badge-emerald' : 'badge-slate' }}">
                                            {{ $status->is_closed ? 'Sí' : 'No' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap justify-end gap-3">
                                            <a href="{{ route('ticket-statuses.show', $status) }}" class="app-btn-secondary">Ver</a>
                                            <a href="{{ route('ticket-statuses.edit', $status) }}" class="app-btn-warning">Editar</a>
                                            <form action="{{ route('ticket-statuses.destroy', $status) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este estado?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="app-btn-danger">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="app-table-row">
                                    <td colspan="5" class="px-6 py-10 text-center text-soft">No hay estados registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($statuses->hasPages())
                    <div class="border-t border-[var(--border-soft)] px-6 py-4 pagination-wrap">
                        {{ $statuses->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>