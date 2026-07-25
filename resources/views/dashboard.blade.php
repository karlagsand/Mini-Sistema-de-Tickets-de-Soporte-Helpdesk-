<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Dashboard Helpdesk</h1>
                <p class="page-subtitle">Panel principal con métricas operativas del sistema de tickets.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="app-badge badge-violet">
                    Rol: {{ $user->role->name ?? 'Sin rol' }}
                </span>

                <a href="{{ route('tickets.index') }}" class="app-btn-primary">
                    Ver tickets
                </a>

                <a href="{{ route('tickets.create') }}" class="app-btn-secondary">
                    Crear ticket
                </a>
            </div>
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

            <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6" aria-label="Resumen principal">
                <div class="metric-card">
                    <p class="metric-label">Total de tickets</p>
                    <p class="metric-value">{{ $stats['total'] }}</p>
                </div>

                <div class="metric-card">
                    <p class="metric-label">Nuevos</p>
                    <p class="text-3xl font-bold text-violet-300 mt-2">{{ $stats['new'] }}</p>
                </div>

                <div class="metric-card">
                    <p class="metric-label">En proceso</p>
                    <p class="text-3xl font-bold text-amber-300 mt-2">{{ $stats['in_progress'] }}</p>
                </div>

                <div class="metric-card">
                    <p class="metric-label">Cerrados</p>
                    <p class="text-3xl font-bold text-emerald-300 mt-2">{{ $stats['closed'] }}</p>
                </div>
            </section>

            <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6" aria-label="Métricas avanzadas">
                <div class="metric-card">
                    <p class="metric-label">Resueltos</p>
                    <p class="text-3xl font-bold text-cyan-300 mt-2">{{ $stats['resolved'] }}</p>
                </div>

                <div class="metric-card">
                    <p class="metric-label">Sin asignar</p>
                    <p class="text-3xl font-bold text-rose-300 mt-2">{{ $stats['unassigned'] }}</p>
                </div>

                <div class="metric-card">
                    <p class="metric-label">Promedio de resolución</p>
                    <p class="text-3xl font-bold text-emerald-300 mt-2">{{ number_format($stats['avg_resolution_hours'], 2) }} h</p>
                </div>

                <div class="metric-card">
                    <p class="metric-label">Promedio de cierre</p>
                    <p class="text-3xl font-bold text-amber-300 mt-2">{{ number_format($stats['avg_closure_hours'], 2) }} h</p>
                </div>
            </section>

            <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 app-card p-6">
                    <h2 class="section-title">Acciones rápidas</h2>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('tickets.index') }}" class="app-btn-primary">
                            Ver tickets
                        </a>

                        <a href="{{ route('tickets.create') }}" class="app-btn-secondary">
                            Crear ticket
                        </a>

                        @if($user->isAdmin())
                            <a href="{{ route('users.index') }}" class="app-btn-secondary">
                                Usuarios
                            </a>

                            <a href="{{ route('categories.index') }}" class="app-btn-secondary">
                                Categorías
                            </a>

                            <a href="{{ route('priorities.index') }}" class="app-btn-secondary">
                                Prioridades
                            </a>

                            <a href="{{ route('ticket-statuses.index') }}" class="app-btn-secondary">
                                Estados
                            </a>
                        @endif
                    </div>
                </div>

                <div class="app-card p-6">
                    <h2 class="section-title">Resumen del usuario</h2>

                    <div class="space-y-3 text-sm text-soft">
                        <p><span class="font-medium text-white">Nombre:</span> {{ $user->name }}</p>
                        <p><span class="font-medium text-white">Correo:</span> {{ $user->email }}</p>
                        <p><span class="font-medium text-white">Rol:</span> {{ $user->role->name ?? 'Sin rol' }}</p>
                        <p><span class="font-medium text-white">Cumplimiento de resolución:</span> {{ number_format($stats['resolution_rate'], 2) }}%</p>
                        <p><span class="font-medium text-white">Cumplimiento de cierre:</span> {{ number_format($stats['closure_rate'], 2) }}%</p>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="app-card p-6">
                    <h2 class="section-title">Tickets más tardados en resolverse</h2>

                    <div class="overflow-x-auto mt-4">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-slate-300 border-b border-white/10">
                                    <th class="py-3 pr-4">Folio</th>
                                    <th class="py-3 pr-4">Asunto</th>
                                    <th class="py-3 pr-4">Horas</th>
                                    <th class="py-3 pr-4">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($slowestTickets as $ticket)
                                    <tr class="border-b border-white/5">
                                        <td class="py-3 pr-4">{{ $ticket->folio }}</td>
                                        <td class="py-3 pr-4">{{ $ticket->subject }}</td>
                                        <td class="py-3 pr-4">{{ $ticket->attention_hours }}</td>
                                        <td class="py-3 pr-4">{{ $ticket->status->name ?? 'Sin estado' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-3 text-soft">Sin datos disponibles.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="app-card p-6">
                    <h2 class="section-title">Tickets más rápidos en resolverse</h2>

                    <div class="overflow-x-auto mt-4">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-slate-300 border-b border-white/10">
                                    <th class="py-3 pr-4">Folio</th>
                                    <th class="py-3 pr-4">Asunto</th>
                                    <th class="py-3 pr-4">Horas</th>
                                    <th class="py-3 pr-4">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fastestTickets as $ticket)
                                    <tr class="border-b border-white/5">
                                        <td class="py-3 pr-4">{{ $ticket->folio }}</td>
                                        <td class="py-3 pr-4">{{ $ticket->subject }}</td>
                                        <td class="py-3 pr-4">{{ $ticket->attention_hours }}</td>
                                        <td class="py-3 pr-4">{{ $ticket->status->name ?? 'Sin estado' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-3 text-soft">Sin datos disponibles.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="app-card p-6">
                    <h2 class="section-title">Tickets abiertos más antiguos</h2>

                    <div class="overflow-x-auto mt-4">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-slate-300 border-b border-white/10">
                                    <th class="py-3 pr-4">Folio</th>
                                    <th class="py-3 pr-4">Asunto</th>
                                    <th class="py-3 pr-4">Horas abierto</th>
                                    <th class="py-3 pr-4">Asignado a</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($oldestOpenTickets as $ticket)
                                    <tr class="border-b border-white/5">
                                        <td class="py-3 pr-4">{{ $ticket->folio }}</td>
                                        <td class="py-3 pr-4">{{ $ticket->subject }}</td>
                                        <td class="py-3 pr-4">{{ $ticket->open_for_hours }}</td>
                                        <td class="py-3 pr-4">{{ $ticket->assignee->name ?? 'Sin asignar' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-3 text-soft">Sin datos disponibles.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="app-card p-6">
                    <h2 class="section-title">Tickets recientes</h2>

                    <div class="overflow-x-auto mt-4">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-slate-300 border-b border-white/10">
                                    <th class="py-3 pr-4">Folio</th>
                                    <th class="py-3 pr-4">Asunto</th>
                                    <th class="py-3 pr-4">Prioridad</th>
                                    <th class="py-3 pr-4">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTickets as $ticket)
                                    <tr class="border-b border-white/5">
                                        <td class="py-3 pr-4">{{ $ticket->folio }}</td>
                                        <td class="py-3 pr-4">{{ $ticket->subject }}</td>
                                        <td class="py-3 pr-4">{{ $ticket->priority->name ?? 'Sin prioridad' }}</td>
                                        <td class="py-3 pr-4">{{ $ticket->status->name ?? 'Sin estado' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-3 text-soft">Sin datos disponibles.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-4 gap-6">
                <div class="app-card p-6">
                    <h2 class="section-title">Tickets por categoría</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        @forelse($ticketsByCategory as $label => $count)
                            <li class="flex items-center justify-between border-b border-white/5 pb-2">
                                <span>{{ $label }}</span>
                                <span class="app-badge badge-blue">{{ $count }}</span>
                            </li>
                        @empty
                            <li class="text-soft">Sin datos disponibles.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="app-card p-6">
                    <h2 class="section-title">Tickets por prioridad</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        @forelse($ticketsByPriority as $label => $count)
                            <li class="flex items-center justify-between border-b border-white/5 pb-2">
                                <span>{{ $label }}</span>
                                <span class="app-badge badge-amber">{{ $count }}</span>
                            </li>
                        @empty
                            <li class="text-soft">Sin datos disponibles.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="app-card p-6">
                    <h2 class="section-title">Tickets por estado</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        @forelse($ticketsByStatus as $label => $count)
                            <li class="flex items-center justify-between border-b border-white/5 pb-2">
                                <span>{{ $label }}</span>
                                <span class="app-badge badge-violet">{{ $count }}</span>
                            </li>
                        @empty
                            <li class="text-soft">Sin datos disponibles.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="app-card p-6">
                    <h2 class="section-title">Tickets por agente</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        @forelse($ticketsByAgent as $label => $count)
                            <li class="flex items-center justify-between border-b border-white/5 pb-2">
                                <span>{{ $label }}</span>
                                <span class="app-badge badge-emerald">{{ $count }}</span>
                            </li>
                        @empty
                            <li class="text-soft">Sin datos disponibles.</li>
                        @endforelse
                    </ul>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>