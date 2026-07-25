<x-app-layout>
    @php
        $priorityClass = function ($priorityName): string {
            $value = strtolower((string) $priorityName);
            if (str_contains($value, 'crítica') || str_contains($value, 'critica') || str_contains($value, 'urgente') || str_contains($value, 'alta')) return 'badge-rose';
            if (str_contains($value, 'media')) return 'badge-amber';
            if (str_contains($value, 'baja')) return 'badge-emerald';
            return 'badge-slate';
        };
        $statusClass = function ($status): string {
            $slug = strtolower((string) ($status?->slug ?? ''));
            if (in_array($slug, ['cerrado', 'resuelto'], true)) return 'badge-emerald';
            if (in_array($slug, ['en-espera-usuario', 'en-espera-proveedor'], true)) return 'badge-amber';
            if ($slug === 'cancelado') return 'badge-slate';
            return 'badge-blue';
        };
        $statusLabel = function ($status): string {
            $slug = strtolower((string) ($status?->slug ?? ''));
            return match ($slug) {
                'nuevo' => 'Recibida',
                'en-revision', 'asignado' => 'En revisión',
                'en-proceso' => 'En atención',
                'en-espera-usuario' => 'Esperando respuesta del solicitante',
                'en-espera-proveedor' => 'En pausa por proveedor',
                'resuelto' => 'Solución registrada',
                'reabierto' => 'Reabierta',
                'cerrado' => 'Finalizada',
                'cancelado' => 'Cancelada',
                default => $status?->name ?? 'Sin estado',
            };
        };
        $attentionClass = function ($level): string {
            if ($level === 'director_general') return 'badge-rose';
            if ($level === 'subdirector') return 'badge-amber';
            if ($level === 'gerente') return 'badge-blue';
            return 'badge-slate';
        };
        $timeLabel = function ($ticket): string {
            $resolutionDueAt = \App\Support\MexicoCityTime::toLocal($ticket->resolution_due_at);
            $now = now(config('app.timezone', 'America/Mexico_City'));

            if (!$resolutionDueAt) return 'Sin tiempo calculado';
            if ($ticket->resolved_at || $ticket->closed_at) return 'Atendido';
            if ($resolutionDueAt->lt($now)) return 'Vencido';
            if ($resolutionDueAt->between($now, $now->copy()->addHours(8))) return 'Por vencer';
            return 'Límite: ' . \App\Support\MexicoCityTime::shortDateTime($resolutionDueAt);
        };
        $barWidth = function ($count, $max): int {
            $max = max((int) $max, 1);
            return max(6, min(100, (int) round(((int) $count / $max) * 100)));
        };
        $maxResolvedDay = max((int) ($agentResolvedByDay->max('count') ?? 1), 1);
        $maxStatus = max((int) ($agentStatusCounts->max() ?? 1), 1);
        $maxPriority = max((int) ($agentPriorityCounts->max() ?? 1), 1);
        $loadLabel = $loadLevel === 'high' ? 'Carga alta' : ($loadLevel === 'medium' ? 'Carga media' : 'Carga controlada');
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Bandeja de trabajo</h1>
                <p class="page-subtitle">Prioridad de atención, casos nuevos y desempeño personal.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('tickets.index', ['view' => 'active', 'order' => 'cola']) }}" class="app-btn-primary">Abrir bandeja</a>
                <a href="{{ route('tickets.index', ['view' => 'history', 'order' => 'recientes']) }}" class="app-btn-secondary">Ver historial</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap space-y-6">
            @include('partials.internal-notifications')
            @if($agentNotifications->count() > 0)
                <section class="agent-alert-grid">
                    @foreach($agentNotifications as $notice)
                        <a href="{{ $notice['url'] }}" class="agent-alert-card {{ $notice['type'] === 'danger' ? 'alert-danger' : ($notice['type'] === 'warning' ? 'alert-warning' : ($notice['type'] === 'success' ? 'alert-success' : 'alert-info')) }}">
                            <span class="agent-alert-icon">{{ $notice['type'] === 'danger' ? '!' : ($notice['type'] === 'warning' ? '⌁' : ($notice['type'] === 'success' ? '✓' : '•')) }}</span>
                            <div>
                                <strong>{{ $notice['title'] }}</strong>
                                <p>{{ $notice['message'] }}</p>
                                <small>{{ $notice['action'] }}</small>
                            </div>
                        </a>
                    @endforeach
                </section>
            @endif

            <section class="agent-hero-panel">
                <div>
                    <p class="eyebrow text-white/80">Mi operación</p>
                    <h2>{{ $loadLabel }}</h2>
                    <p>Tu bandeja se organiza por tiempo objetivo, prioridad calculada y nivel de atención del solicitante.</p>
                </div>
                <div class="agent-hero-metrics">
                    <div><strong>{{ $activeTickets }}</strong><span>Activos</span></div>
                    <div><strong>{{ $newAssignedTickets }}</strong><span>Nuevos</span></div>
                    <div><strong>{{ $dueSoonTickets }}</strong><span>Por vencer</span></div>
                    <div><strong>{{ $overdueTickets }}</strong><span>Vencidos</span></div>
                </div>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                <div class="xl:col-span-8 app-card p-6">
                    <div class="flex items-start justify-between gap-4 mb-5">
                        <div>
                            <h2 class="section-title">Prioridad de atención</h2>
                            <p class="text-soft text-sm">Atiende primero los casos con vencimiento cercano, mayor criticidad o mayor nivel de atención.</p>
                        </div>
                        <a href="{{ route('tickets.index', ['view' => 'active', 'order' => 'cola']) }}" class="app-btn-secondary">Ver todo</a>
                    </div>

                    <div class="space-y-4">
                        @forelse($priorityQueue as $ticket)
                            <a href="{{ route('tickets.show', $ticket) }}" class="work-queue-card">
                                <div class="queue-rank">{{ $loop->iteration }}</div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0">
                                            <p class="font-bold text-[var(--text-main)] truncate">{{ $ticket->subject }}</p>
                                            <p class="text-xs text-soft mt-1">{{ $ticket->folio }} · {{ $ticket->creator->name ?? 'Sin solicitante' }} · {{ $ticket->category->name ?? 'Sin área' }}</p>
                                        </div>
                                        <div class="flex flex-wrap gap-2 lg:justify-end">
                                            <span class="app-badge {{ $priorityClass($ticket->priority?->name ?? 'Sin prioridad') }}">{{ $ticket->priority->name ?? 'Sin prioridad' }}</span>
                                            <span class="app-badge {{ $statusClass($ticket->status) }}">{{ $statusLabel($ticket->status) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        <span class="app-badge {{ $attentionClass($ticket->creator?->position_level ?? 'operativo') }}">{{ $ticket->creator?->attentionLabel() ?? 'Operativo' }}</span>
                                        <span class="app-badge {{ is_null($ticket->assigned_to) ? 'badge-amber' : 'badge-slate' }}">{{ is_null($ticket->assigned_to) ? 'Disponible' : 'Asignado a ti' }}</span>
                                        <span class="app-badge badge-blue">{{ $timeLabel($ticket) }}</span>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="empty-state py-12">
                                <p class="font-semibold text-[var(--text-main)]">Tu bandeja está al día.</p>
                                <p class="text-soft text-sm mt-1">No tienes casos activos ni casos disponibles pendientes.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <aside class="xl:col-span-4 space-y-6">
                    <div class="app-card p-6">
                        <h2 class="section-title">Mi desempeño</h2>
                        <div class="grid grid-cols-2 gap-4 mt-5">
                            <div class="compact-donut-panel">
                                <div class="donut-chart donut-blue" style="--value: {{ max(0, min(100, $agentResponseCompliance)) }};"><span>{{ $agentResponseCompliance }}%</span></div>
                                <p>Primera respuesta</p>
                            </div>
                            <div class="compact-donut-panel">
                                <div class="donut-chart donut-purple" style="--value: {{ max(0, min(100, $agentSolutionCompliance)) }};"><span>{{ $agentSolutionCompliance }}%</span></div>
                                <p>Solución</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mt-4">
                            <div class="info-panel"><p class="info-label">Prom. solución</p><p class="info-value">{{ $avgMyResolutionHours }} h</p></div>
                            <div class="info-panel"><p class="info-label">Satisfacción</p><p class="info-value">{{ $agentAvgSatisfaction > 0 ? $agentAvgSatisfaction : 'N/D' }}</p></div>
                        </div>
                    </div>

                    <div class="app-card p-6">
                        <h2 class="section-title">Soluciones recientes</h2>
                        <p class="text-soft text-sm mb-5">Casos con solución registrada en los últimos 7 días.</p>
                        <div class="mini-column-chart">
                            @foreach($agentResolvedByDay as $day)
                                <div><span>{{ $day['count'] }}</span><i style="height: {{ $barWidth($day['count'], $maxResolvedDay) }}%"></i><small>{{ $day['label'] }}</small></div>
                            @endforeach
                        </div>
                    </div>
                </aside>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="app-card p-6">
                    <h2 class="section-title">Casos disponibles</h2>
                    <p class="text-soft text-sm mb-4">Casos sin responsable que puedes tomar.</p>
                    <div class="space-y-3">
                        @forelse($recentAvailableTickets as $ticket)
                            <a href="{{ route('tickets.show', $ticket) }}" class="simple-ticket-card">
                                <strong>{{ $ticket->subject }}</strong>
                                <span>{{ $ticket->folio }} · {{ $ticket->category->name ?? 'Sin área' }}</span>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <span class="app-badge {{ $priorityClass($ticket->priority?->name ?? 'Sin prioridad') }}">{{ $ticket->priority->name ?? 'Sin prioridad' }}</span>
                                    <span class="app-badge {{ $attentionClass($ticket->creator?->position_level ?? 'operativo') }}">{{ $ticket->creator?->attentionLabel() ?? 'Operativo' }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="empty-state py-8"><p class="font-semibold">No hay casos disponibles.</p><p class="text-soft text-sm mt-1">Cuando exista un caso sin responsable, aparecerá aquí.</p></div>
                        @endforelse
                    </div>
                </div>

                <div class="app-card p-6">
                    <h2 class="section-title">Mis casos por estado</h2>
                    <div class="space-y-3 mt-4">
                        @forelse($agentStatusCounts as $label => $count)
                            <div class="chart-row"><div class="chart-row-label">{{ $label }}</div><div class="chart-track"><div class="chart-bar chart-bar-muted" style="width: {{ $barWidth($count, $maxStatus) }}%"></div></div><div class="chart-row-value">{{ $count }}</div></div>
                        @empty <p class="text-soft text-sm">Sin información.</p> @endforelse
                    </div>
                </div>

                <div class="app-card p-6">
                    <h2 class="section-title">Mis casos por prioridad</h2>
                    <div class="space-y-3 mt-4">
                        @forelse($agentPriorityCounts as $label => $count)
                            <div class="chart-row"><div class="chart-row-label">{{ $label }}</div><div class="chart-track"><div class="chart-bar" style="width: {{ $barWidth($count, $maxPriority) }}%"></div></div><div class="chart-row-value">{{ $count }}</div></div>
                        @empty <p class="text-soft text-sm">Sin información.</p> @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
