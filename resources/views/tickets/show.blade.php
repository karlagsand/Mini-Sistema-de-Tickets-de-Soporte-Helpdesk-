<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">{{ $ticket->folio }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ $ticket->subject }}</p>
            </div>
            <a href="{{ route('tickets.index') }}"
               class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 transition">
                Volver
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Detalle del ticket</h3>

                    <div class="space-y-4 text-sm text-slate-700">
                        <p><span class="font-semibold">Asunto:</span> {{ $ticket->subject }}</p>
                        <p><span class="font-semibold">Descripción:</span> {{ $ticket->description }}</p>
                        <p><span class="font-semibold">Categoría:</span> {{ $ticket->category->name }}</p>
                        <p><span class="font-semibold">Prioridad:</span> {{ $ticket->priority->name }}</p>
                        <p><span class="font-semibold">Estado:</span> {{ $ticket->status->name }}</p>
                        <p><span class="font-semibold">Creado por:</span> {{ $ticket->creator->name }}</p>
                        <p><span class="font-semibold">Asignado a:</span> {{ $ticket->assignee->name ?? 'Sin asignar' }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Gestión</h3>

                    @if(!auth()->user()->isUserRole())
                        <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Asignar agente</label>
                                <select name="assigned_to" class="w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Sin asignar</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}" @selected($ticket->assigned_to == $agent->id)>
                                            {{ $agent->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Cambiar estado</label>
                                <select name="status_id" class="w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                    @foreach($statuses as $status)
                                        <option value="{{ $status->id }}" @selected($ticket->status_id == $status->id)>
                                            {{ $status->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit"
                                    class="w-full px-4 py-2 rounded-xl bg-slate-800 text-white hover:bg-slate-700 transition">
                                Guardar cambios
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Comentarios</h3>

                    <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" class="space-y-4 mb-6">
                        @csrf
                        <textarea name="comment" rows="4"
                                  class="w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="Escribe un comentario..." required></textarea>

                        <button type="submit"
                                class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-500 transition">
                            Agregar comentario
                        </button>
                    </form>

                    <div class="space-y-4">
                        @forelse($ticket->comments->sortByDesc('created_at') as $comment)
                            <div class="border border-slate-200 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium text-slate-800">{{ $comment->user->name }}</span>
                                    <span class="text-xs text-slate-500">{{ $comment->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <p class="text-sm text-slate-700">{{ $comment->comment }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Aún no hay comentarios.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Historial de estados</h3>

                    <div class="space-y-4">
                        @forelse($ticket->histories->sortByDesc('changed_at') as $history)
                            <div class="border-l-4 border-blue-500 pl-4">
                                <p class="text-sm text-slate-700">
                                    <span class="font-semibold">{{ $history->changedBy->name }}</span>
                                    cambió el estado a
                                    <span class="font-semibold">{{ $history->newStatus->name }}</span>
                                </p>
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ $history->changed_at->format('d/m/Y H:i') }}
                                </p>
                                @if($history->notes)
                                    <p class="text-sm text-slate-600 mt-1">{{ $history->notes }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No hay historial registrado.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>