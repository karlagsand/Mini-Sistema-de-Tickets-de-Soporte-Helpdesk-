<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Categorías</h2>
            <a href="{{ route('categories.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-500 transition">
                Nueva categoría
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
                                <th class="px-4 py-3 text-left">Descripción</th>
                                <th class="px-4 py-3 text-left">Estado</th>
                                <th class="px-4 py-3 text-left">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($categories as $category)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">{{ $category->name }}</td>
                                    <td class="px-4 py-3">{{ $category->description ?? 'Sin descripción' }}</td>
                                    <td class="px-4 py-3">
                                        {{ $category->is_active ? 'Activa' : 'Inactiva' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-3">
                                            <a href="{{ route('categories.show', $category) }}" class="text-blue-600 hover:text-blue-800 font-medium">Ver</a>
                                            <a href="{{ route('categories.edit', $category) }}" class="text-amber-600 hover:text-amber-800 font-medium">Editar</a>
                                            <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-slate-500">
                                        No hay categorías registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">
                    {{ $categories->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>