<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Tickets</h2>
            <a href="{{ route('tickets.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-500 transition">
                Nuevo ticket
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

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <select name="status" class="rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos los estados</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->id }}" @selected(request('status') == $status->id)>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="priority" class="rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todas las prioridades</option>
                        @foreach($priorities as $priority)
                            <option value="{{ $priority->id }}" @selected(request('priority') == $priority->id)>
                                {{ $priority->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="category" class="rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todas las categorías</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit"
                            class="rounded-xl bg-slate-800 text-white px-4 py-2 hover:bg-slate-700 transition">
                        Filtrar
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left">Folio</th>
                                <th class="px-4 py-3 text-left">Asunto</th>
                                <th class="px-4 py-3 text-left">Categoría</th>
                                <th class="px-4 py-3 text-left">Prioridad</th>
                                <th class="px-4 py-3 text-left">Estado</th>
                                <th class="px-4 py-3 text-left">Asignado</th>
                                <th class="px-4 py-3 text-left">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($tickets as $ticket)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium text-slate-700">{{ $ticket->folio }}</td>
                                    <td class="px-4 py-3">{{ $ticket->subject }}</td>
                                    <td class="px-4 py-3">{{ $ticket->category->name }}</td>
                                    <td class="px-4 py-3">{{ $ticket->priority->name }}</td>
                                    <td class="px-4 py-3">{{ $ticket->status->name }}</td>
                                    <td class="px-4 py-3">{{ $ticket->assignee->name ?? 'Sin asignar' }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('tickets.show', $ticket) }}"
                                           class="text-blue-600 hover:text-blue-800 font-medium">
                                            Ver detalle
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-slate-500">
                                        No hay tickets registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">
                    {{ $tickets->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>