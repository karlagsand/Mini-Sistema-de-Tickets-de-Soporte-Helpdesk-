<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Estados de ticket</h2>
            <a href="{{ route('ticket-statuses.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-500 transition">
                Nuevo estado
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-emerald-100 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left">Nombre</th>
                                <th class="px-4 py-3 text-left">Slug</th>
                                <th class="px-4 py-3 text-left">Orden</th>
                                <th class="px-4 py-3 text-left">Cierre</th>
                                <th class="px-4 py-3 text-left">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($statuses as $status)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">{{ $status->name }}</td>
                                    <td class="px-4 py-3">{{ $status->slug }}</td>
                                    <td class="px-4 py-3">{{ $status->sort_order }}</td>
                                    <td class="px-4 py-3">{{ $status->is_closed ? 'Sí' : 'No' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-3">
                                            <a href="{{ route('ticket-statuses.show', $status) }}" class="text-blue-600 hover:text-blue-800 font-medium">Ver</a>
                                            <a href="{{ route('ticket-statuses.edit', $status) }}" class="text-amber-600 hover:text-amber-800 font-medium">Editar</a>
                                            <form action="{{ route('ticket-statuses.destroy', $status) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este estado?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-slate-500">
                                        No hay estados registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">
                    {{ $statuses->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>