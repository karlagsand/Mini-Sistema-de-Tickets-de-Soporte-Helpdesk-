<x-app-layout>
    @php
        $barWidth = function ($count, $max): int {
            $max = max((int) $max, 1);
            return max(6, min(100, (int) round(((int) $count / $max) * 100)));
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
        $priorityClass = function ($priorityName): string {
            $value = strtolower((string) $priorityName);
            if (str_contains($value, 'crítica') || str_contains($value, 'critica') || str_contains($value, 'urgente') || str_contains($value, 'alta')) return 'badge-rose';
            if (str_contains($value, 'media')) return 'badge-amber';
            if (str_contains($value, 'baja')) return 'badge-emerald';
            return 'badge-slate';
        };
        $maxDay = max((int) ($ticketsCreatedByDay->max('count') ?? 1), 1);
        $maxStatus = max((int) ($ticketsByStatus->max() ?? 1), 1);
        $maxCategory = max((int) ($ticketsByCategory->max() ?? 1), 1);
        $maxRequestType = max((int) ($ticketsByRequestType->max() ?? 1), 1);
        $maxUserLevel = max((int) ($ticketsByUserLevel->max() ?? 1), 1);
        $maxAgentLoad = max((int) ($agentsLoad->max('active_tickets') ?? 1), 1);
        $maxPriority = max((int) ($ticketsByPriority->max() ?? 1), 1);
        $maxSla = max((int) ($ticketsBySlaState->max() ?? 1), 1);
        $operationRisk = $activeTickets > 0 ? min(100, round((($overdueTickets * 1.4 + $dueSoonTickets + $unassignedTickets) / max($activeTickets, 1)) * 100)) : 0;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between no-print">
            <div>
                <h1 class="page-title">Panel ejecutivo</h1>
                <p class="page-subtitle">Indicadores de operación, tiempos de atención, carga de agentes y oportunidades de mejora.</p>
            </div>
            <div class="dashboard-quick-actions" aria-label="Accesos rápidos de supervisión">
                <span class="quick-actions-label">Accesos rápidos</span>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('tickets.index', ['assignment' => 'sin_asignar']) }}" class="dashboard-action-btn" title="Muestra la lista filtrada de casos activos que aún no tienen responsable asignado.">
                        Ver sin responsable
                        <strong>{{ $unassignedTickets }}</strong>
                    </a>
                    <a href="{{ route('tickets.index', ['time_status' => 'vencido']) }}" class="dashboard-action-btn" title="Muestra la lista filtrada de casos que ya superaron el tiempo objetivo de respuesta o solución.">
                        Ver tiempos vencidos
                        <strong>{{ $overdueTickets }}</strong>
                    </a>
                    <a href="{{ route('dashboard.admin.report.pdf') }}" class="app-btn-primary">Exportar PDF</a>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap space-y-6 screen-dashboard">
            <section class="executive-hero">
                <div class="executive-hero-main">
                    <p class="eyebrow text-white/80">Mesa de ayuda</p>
                    <h2>Centro de control operativo</h2>
                    <p>Vista ejecutiva para detectar carga, retrasos, cumplimiento de tiempos y oportunidades de mejora.</p>
                    <div class="hero-actions">
                        <a href="{{ route('tickets.index') }}" class="hero-action">Supervisar tickets</a>
                        <a href="{{ route('tickets.index', ['order' => 'criticidad']) }}" class="hero-action hero-action-soft">Ver criticidad</a>
                    </div>
                </div>
                <div class="hero-rings">
                    <div class="ring-card">
                        <div class="donut-chart donut-blue" style="--value: {{ max(0, min(100, $responseCompliance)) }};">
                            <span>{{ $responseCompliance }}%</span>
                        </div>
                        <p>Primera respuesta</p>
                    </div>
                    <div class="ring-card">
                        <div class="donut-chart donut-purple" style="--value: {{ max(0, min(100, $solutionCompliance)) }};">
                            <span>{{ $solutionCompliance }}%</span>
                        </div>
                        <p>Solución</p>
                    </div>
                    <div class="ring-card">
                        <div class="donut-chart donut-risk" style="--value: {{ $operationRisk }};">
                            <span>{{ $operationRisk }}%</span>
                        </div>
                        <p>Riesgo operativo</p>
                    </div>
                </div>
            </section>

            <section class="dashboard-kpi-grid">
                <div class="dashboard-kpi-card kpi-primary"><span class="kpi-icon">📌</span><p class="metric-label">Casos activos</p><p class="metric-value">{{ $activeTickets }}</p><p class="metric-note">Pendientes de atención o cierre.</p></div>
                <div class="dashboard-kpi-card"><span class="kpi-icon">👤</span><p class="metric-label">Sin responsable</p><p class="metric-value">{{ $unassignedTickets }}</p><p class="metric-note">Requieren asignación.</p></div>
                <div class="dashboard-kpi-card kpi-warning"><span class="kpi-icon">⏱</span><p class="metric-label">Por vencer</p><p class="metric-value">{{ $dueSoonTickets }}</p><p class="metric-note">Atención en próximas horas.</p></div>
                <div class="dashboard-kpi-card kpi-danger"><span class="kpi-icon">⚠</span><p class="metric-label">Vencidos</p><p class="metric-value">{{ $overdueTickets }}</p><p class="metric-note">Riesgo de incumplimiento.</p></div>
                <div class="dashboard-kpi-card"><span class="kpi-icon">✅</span><p class="metric-label">Finalizados</p><p class="metric-value">{{ $closedTickets }}</p><p class="metric-note">Solicitudes concluidas.</p></div>
                <div class="dashboard-kpi-card"><span class="kpi-icon">★</span><p class="metric-label">Satisfacción promedio</p><p class="metric-value">{{ $avgSatisfaction > 0 ? $avgSatisfaction . ' / 5' : 'N/D' }}</p><p class="metric-note">Calificación del usuario. {{ $ratedTicketsCount }} evaluación(es).</p></div>
            </section>

            @if($alerts->count() > 0)
                <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    @foreach($alerts as $alert)
                        <a href="{{ route('tickets.index') }}" class="alert-tile {{ $alert['type'] === 'danger' ? 'alert-danger' : ($alert['type'] === 'warning' ? 'alert-warning' : 'alert-info') }}">
                            <strong>{{ $alert['title'] }}</strong>
                            <span>{{ $alert['message'] }}</span>
                        </a>
                    @endforeach
                </section>
            @endif

            <section class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                <div class="app-card p-6 xl:col-span-7">
                    <div class="flex items-start justify-between gap-4 mb-5">
                        <div>
                            <h2 class="section-title">Tendencia de solicitudes</h2>
                            <p class="text-soft text-sm">Solicitudes registradas durante los últimos 14 días.</p>
                        </div>
                        <span class="app-badge badge-blue">{{ $todayTickets }} hoy</span>
                    </div>
                    <div class="executive-column-chart" aria-label="Tendencia de solicitudes">
                        @foreach($ticketsCreatedByDay as $day)
                            <div class="executive-column-item">
                                <span>{{ $day['count'] }}</span>
                                <div class="executive-column" style="height: {{ $barWidth($day['count'], $maxDay) }}%"></div>
                                <small>{{ $day['label'] }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="app-card p-6 xl:col-span-5">
                    <h2 class="section-title">Cumplimiento de tiempos de atención</h2>
                    <p class="text-soft text-sm mb-5">Mide si la primera respuesta y la solución se registran dentro del tiempo objetivo calculado para cada caso.</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="compact-donut-panel">
                            <div class="donut-chart donut-blue" style="--value: {{ max(0, min(100, $responseCompliance)) }};"><span>{{ $responseCompliance }}%</span></div>
                            <p>Primera respuesta</p>
                            <small>Prom. {{ $avgFirstResponseHours }} h</small>
                        </div>
                        <div class="compact-donut-panel">
                            <div class="donut-chart donut-purple" style="--value: {{ max(0, min(100, $solutionCompliance)) }};"><span>{{ $solutionCompliance }}%</span></div>
                            <p>Solución</p>
                            <small>Prom. {{ $avgResolutionHours }} h</small>
                        </div>
                    </div>
                    <div class="mt-5 space-y-3">
                        @foreach($ticketsBySlaState as $label => $count)
                            <div class="chart-row compact-row">
                                <div class="chart-row-label">{{ $label }}</div>
                                <div class="chart-track"><div class="chart-bar chart-bar-muted" style="width: {{ $barWidth($count, $maxSla) }}%"></div></div>
                                <div class="chart-row-value">{{ $count }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                <div class="app-card p-6 xl:col-span-7">
                    <div class="flex items-start justify-between gap-4 mb-5">
                        <div>
                            <h2 class="section-title">Carga y desempeño de agentes</h2>
                            <p class="text-soft text-sm">Permite detectar saturación, retrasos y balance operativo.</p>
                        </div>
                        <a href="{{ route('tickets.index') }}" class="app-btn-secondary">Gestionar</a>
                    </div>
                    <div class="agent-performance-grid">
                        @forelse($agentsLoad as $agent)
                            <div class="agent-performance-card">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-bold text-[var(--text-main)] truncate">{{ $agent['name'] }}</p>
                                        <p class="text-xs text-soft mt-1">{{ $agent['email'] }}</p>
                                    </div>
                                    <span class="app-badge {{ $agent['load_level'] === 'high' ? 'badge-rose' : ($agent['load_level'] === 'medium' ? 'badge-amber' : 'badge-emerald') }}">
                                        {{ $agent['load_level'] === 'high' ? 'Alta' : ($agent['load_level'] === 'medium' ? 'Media' : 'Baja') }}
                                    </span>
                                </div>
                                <div class="mini-stats">
                                    <span><strong>{{ $agent['active_tickets'] }}</strong> activos</span>
                                    <span><strong>{{ $agent['resolved_tickets'] }}</strong> con solución</span>
                                    <span><strong>{{ $agent['overdue_tickets'] }}</strong> vencidos</span>
                                </div>
                                <div class="chart-track mt-4"><div class="chart-bar" style="width: {{ $barWidth($agent['active_tickets'], $maxAgentLoad) }}%"></div></div>
                                <p class="text-xs text-soft mt-2">Promedio de solución: {{ $agent['avg_resolution_hours'] }} h</p>
                            </div>
                        @empty
                            <div class="empty-state"><p class="font-semibold">No hay agentes registrados.</p></div>
                        @endforelse
                    </div>
                </div>

                <aside class="app-card p-6 xl:col-span-5">
                    <h2 class="section-title">Atención prioritaria</h2>
                    <p class="text-soft text-sm mb-4">Casos que conviene supervisar primero por tiempo, criticidad o nivel de atención.</p>
                    <div class="space-y-3">
                        @forelse($topRiskTickets as $ticket)
                            <a href="{{ route('tickets.show', $ticket) }}" class="risk-ticket-card">
                                <div class="min-w-0">
                                    <strong>{{ $ticket->folio }}</strong>
                                    <span>{{ $ticket->subject }}</span>
                                    <small>{{ $ticket->creator?->name ?? 'Sin solicitante' }} · {{ $ticket->category?->name ?? 'Sin área' }}</small>
                                </div>
                                <div class="flex flex-col gap-1 items-end">
                                    <span class="app-badge {{ $priorityClass($ticket->priority?->name ?? '') }}">{{ $ticket->priority?->name ?? 'Sin prioridad' }}</span>
                                    <span class="app-badge {{ $statusClass($ticket->status) }}">{{ $statusLabel($ticket->status) }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="empty-state py-8"><p class="font-semibold">No hay riesgos activos.</p><p class="text-soft text-sm mt-1">La operación no muestra casos urgentes en este momento.</p></div>
                        @endforelse
                    </div>
                </aside>
            </section>

            <section class="admin-distribution-grid grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-6">
                <div class="app-card p-6">
                    <h2 class="section-title">Estado de solicitudes</h2>
                    <div class="space-y-3 mt-4">
                        @forelse($ticketsByStatus as $label => $count)
                            <div class="chart-row"><div class="chart-row-label" title="{{ $label }}">{{ $label }}</div><div class="chart-track"><div class="chart-bar chart-bar-muted" style="width: {{ $barWidth($count, $maxStatus) }}%"></div></div><div class="chart-row-value">{{ $count }}</div></div>
                        @empty <p class="text-soft text-sm">Sin información.</p> @endforelse
                    </div>
                </div>
                <div class="app-card p-6">
                    <h2 class="section-title">Tipo de apoyo</h2>
                    <div class="space-y-3 mt-4">
                        @forelse($ticketsByRequestType as $label => $count)
                            <div class="chart-row"><div class="chart-row-label" title="{{ $label }}">{{ $label }}</div><div class="chart-track"><div class="chart-bar chart-bar-muted" style="width: {{ $barWidth($count, $maxRequestType) }}%"></div></div><div class="chart-row-value">{{ $count }}</div></div>
                        @empty <p class="text-soft text-sm">Sin información.</p> @endforelse
                    </div>
                </div>
                <div class="app-card p-6">
                    <h2 class="section-title">Áreas con mayor demanda</h2>
                    <div class="space-y-3 mt-4">
                        @forelse($ticketsByCategory as $label => $count)
                            <div class="chart-row"><div class="chart-row-label" title="{{ $label }}">{{ $label }}</div><div class="chart-track"><div class="chart-bar chart-bar-muted" style="width: {{ $barWidth($count, $maxCategory) }}%"></div></div><div class="chart-row-value">{{ $count }}</div></div>
                        @empty <p class="text-soft text-sm">Sin información.</p> @endforelse
                    </div>
                </div>
                <div class="app-card p-6">
                    <h2 class="section-title">Nivel de atención</h2>
                    <div class="space-y-3 mt-4">
                        @forelse($ticketsByUserLevel as $label => $count)
                            <div class="chart-row"><div class="chart-row-label" title="{{ $label }}">{{ $label }}</div><div class="chart-track"><div class="chart-bar chart-bar-muted" style="width: {{ $barWidth($count, $maxUserLevel) }}%"></div></div><div class="chart-row-value">{{ $count }}</div></div>
                        @empty <p class="text-soft text-sm">Sin información.</p> @endforelse
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
                <div class="app-card p-6 xl:col-span-2">
                    <h2 class="section-title">Prioridad interna</h2>
                    <p class="text-soft text-sm mb-5">Distribución de criticidad calculada automáticamente para organizar la operación.</p>
                    <div class="space-y-3">
                        @forelse($ticketsByPriority as $label => $count)
                            <div class="chart-row"><div class="chart-row-label">{{ $label }}</div><div class="chart-track"><div class="chart-bar" style="width: {{ $barWidth($count, $maxPriority) }}%"></div></div><div class="chart-row-value">{{ $count }}</div></div>
                        @empty <p class="text-soft text-sm">Sin información.</p> @endforelse
                    </div>
                </div>
                <aside class="app-card p-6">
                    <h2 class="section-title">Oportunidades de mejora</h2>
                    <p class="text-soft text-sm mb-4">Acciones sugeridas con base en la operación actual.</p>
                    <div class="space-y-3">
                        @foreach($opportunities as $item)
                            <div class="decision-card">
                                <p class="font-semibold text-[var(--text-main)]">{{ $item['title'] }}</p>
                                <p class="text-sm text-soft mt-1 leading-6">{{ $item['message'] }}</p>
                                <a href="{{ $item['url'] }}" class="text-sm font-semibold mt-3 inline-flex">{{ $item['action'] }}</a>
                            </div>
                        @endforeach
                    </div>
                </aside>
            </section>
        </div>

        <section id="adminPrintableReport" class="print-only executive-report-print">
            <div class="report-page">
                <div class="report-header">
                    <div>
                        <p class="report-eyebrow">Helpdesk · Reporte ejecutivo</p>
                        <h1>Resumen de operación de mesa de ayuda</h1>
                        <p>Generado el {{ $generatedAt->format('d/m/Y H:i') }} · Hora CDMX</p>
                    </div>
                    <div class="report-score">
                        <strong>{{ $responseCompliance }}%</strong>
                        <span>cumplimiento de primera respuesta</span>
                    </div>
                </div>

                <div class="report-kpis report-kpis-eight">
                    <div><span>Casos activos</span><strong>{{ $activeTickets }}</strong></div>
                    <div><span>Sin responsable</span><strong>{{ $unassignedTickets }}</strong></div>
                    <div><span>Por vencer</span><strong>{{ $dueSoonTickets }}</strong></div>
                    <div><span>Vencidos</span><strong>{{ $overdueTickets }}</strong></div>
                    <div><span>Finalizados</span><strong>{{ $closedTickets }}</strong></div>
                    <div><span>Solución en tiempo</span><strong>{{ $solutionCompliance }}%</strong></div>
                    <div><span>Prom. respuesta</span><strong>{{ $avgFirstResponseHours }} h</strong></div>
                    <div><span>Satisfacción</span><strong>{{ $avgSatisfaction > 0 ? $avgSatisfaction . '/5' : 'N/D' }}</strong></div>
                </div>

                <div class="report-grid-two">
                    <div class="report-box">
                        <h2>Tendencia reciente</h2>
                        <div class="report-bars">
                            @foreach($ticketsCreatedByDay->take(-10) as $day)
                                <div><span style="height: {{ $barWidth($day['count'], $maxDay) }}%"></span><small>{{ $day['label'] }}</small></div>
                            @endforeach
                        </div>
                    </div>
                    <div class="report-box">
                        <h2>Cumplimiento de tiempos de atención</h2>
                        <div class="report-progress"><label>Primera respuesta</label><span><i style="width: {{ max(4, min(100, (int) $responseCompliance)) }}%"></i></span><strong>{{ $responseCompliance }}%</strong></div>
                        <div class="report-progress"><label>Solución</label><span><i style="width: {{ max(4, min(100, (int) $solutionCompliance)) }}%"></i></span><strong>{{ $solutionCompliance }}%</strong></div>
                        <p>Promedios: primera respuesta {{ $avgFirstResponseHours }} h · solución {{ $avgResolutionHours }} h · cierre {{ $avgClosureHours }} h.</p>
                        <div class="report-mini-grid">
                            @foreach($ticketsBySlaState as $label => $count)
                                <div><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="report-grid-four">
                    <div class="report-box">
                        <h2>Estado</h2>
                        @foreach($ticketsByStatus->take(5) as $label => $count)
                            <div class="report-row"><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                        @endforeach
                    </div>
                    <div class="report-box">
                        <h2>Tipo de apoyo</h2>
                        @foreach($ticketsByRequestType->take(5) as $label => $count)
                            <div class="report-row"><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                        @endforeach
                    </div>
                    <div class="report-box">
                        <h2>Áreas</h2>
                        @foreach($ticketsByCategory->take(5) as $label => $count)
                            <div class="report-row"><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                        @endforeach
                    </div>
                    <div class="report-box">
                        <h2>Nivel de atención</h2>
                        @foreach($ticketsByUserLevel->take(5) as $label => $count)
                            <div class="report-row"><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="report-page report-page-break">
                <div class="report-section-title">
                    <h1>Operación, responsables y riesgos</h1>
                    <p>Detalle resumido para seguimiento administrativo y mejora continua.</p>
                </div>

                <div class="report-grid-three">
                    <div class="report-box report-box-large">
                        <h2>Carga de agentes</h2>
                        @forelse($agentsLoad->take(7) as $agent)
                            <div class="report-row"><span>{{ $agent['name'] }}</span><strong>{{ $agent['active_tickets'] }} activos · {{ $agent['overdue_tickets'] }} vencidos</strong></div>
                        @empty
                            <p>No hay agentes registrados.</p>
                        @endforelse
                    </div>
                    <div class="report-box report-box-large">
                        <h2>Prioridad interna</h2>
                        @foreach($ticketsByPriority->take(7) as $label => $count)
                            <div class="report-row"><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                        @endforeach
                    </div>
                    <div class="report-box report-box-large">
                        <h2>Atención prioritaria</h2>
                        @forelse($topRiskTickets->take(7) as $ticket)
                            <div class="report-row"><span>{{ $ticket->folio }} · {{ str($ticket->subject)->limit(36) }}</span><strong>{{ $ticket->priority?->name ?? 'Sin prioridad' }}</strong></div>
                        @empty
                            <p>No hay riesgos activos.</p>
                        @endforelse
                    </div>
                </div>

                <div class="report-grid-two report-grid-bottom">
                    <div class="report-box">
                        <h2>Solicitudes de alto nivel pendientes</h2>
                        @forelse($highLevelPendingTickets->take(6) as $ticket)
                            <div class="report-row"><span>{{ $ticket->folio }} · {{ $ticket->creator?->attentionLabel() ?? 'Sin nivel' }}</span><strong>{{ $statusLabel($ticket->status) }}</strong></div>
                        @empty
                            <p>No hay solicitudes de alto nivel pendientes.</p>
                        @endforelse
                    </div>
                    <div class="report-box">
                        <h2>Oportunidades de mejora</h2>
                        @foreach($opportunities->take(6) as $item)
                            <div class="report-row"><span>{{ $item['title'] }}</span><strong>Revisar</strong></div>
                        @endforeach
                    </div>
                </div>

                <div class="report-footer">
                    <strong>Lectura ejecutiva:</strong>
                    Los accesos “sin responsable” y “tiempos vencidos” del dashboard abren listas filtradas para revisar los casos detrás de esos indicadores. Este reporte concentra los mismos indicadores del panel en formato compacto.
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
