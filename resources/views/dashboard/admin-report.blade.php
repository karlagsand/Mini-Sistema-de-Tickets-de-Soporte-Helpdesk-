<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte de mesa de ayuda</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="printable-report">
    @php
        $barWidth = function ($count, $max): int {
            $max = max((int) $max, 1);
            return max(6, min(100, (int) round(((int) $count / $max) * 100)));
        };
        $maxCategory = max((int) ($ticketsByCategory->max() ?? 1), 1);
        $maxRequestType = max((int) ($ticketsByRequestType->max() ?? 1), 1);
        $maxUserLevel = max((int) ($ticketsByUserLevel->max() ?? 1), 1);
        $maxSla = max((int) ($ticketsBySlaState->max() ?? 1), 1);
        $maxDay = max((int) ($ticketsCreatedByDay->max('count') ?? 1), 1);
        $maxAgentLoad = max((int) ($agentsLoad->max('active_tickets') ?? 1), 1);
    @endphp

    <main class="page-wrap py-8 space-y-6">
        <header class="app-card p-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="eyebrow">Reporte ejecutivo</p>
                <h1 class="page-title">Mesa de ayuda</h1>
                <p class="page-subtitle">Generado el {{ \App\Support\MexicoCityTime::dateTime($generatedAt) }}</p>
            </div>
            <div class="no-print flex gap-3">
                <button onclick="window.print()" class="app-btn-primary">Guardar como PDF</button>
                <a href="{{ route('dashboard') }}" class="app-btn-secondary">Volver</a>
            </div>
        </header>

        <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="metric-card"><p class="metric-label">Total</p><p class="metric-value">{{ $totalTickets }}</p></div>
            <div class="metric-card"><p class="metric-label">Activos</p><p class="metric-value">{{ $activeTickets }}</p></div>
            <div class="metric-card"><p class="metric-label">Vencidos</p><p class="metric-value">{{ $overdueTickets }}</p></div>
            <div class="metric-card"><p class="metric-label">Sin responsable</p><p class="metric-value">{{ $unassignedTickets }}</p></div>
            <div class="metric-card"><p class="metric-label">Respuesta</p><p class="metric-value">{{ $responseCompliance }}%</p></div>
            <div class="metric-card"><p class="metric-label">Solución</p><p class="metric-value">{{ $solutionCompliance }}%</p></div>
            <div class="metric-card"><p class="metric-label">Prom. solución</p><p class="metric-value">{{ $avgResolutionHours }} h</p></div>
            <div class="metric-card"><p class="metric-label">Satisfacción</p><p class="metric-value">{{ $avgSatisfaction > 0 ? $avgSatisfaction : 'N/D' }}</p></div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="app-card p-6">
                <h2 class="section-title">Cumplimiento de tiempos</h2>
                <div class="space-y-3 mt-4">
                    @foreach($ticketsBySlaState as $label => $count)
                        <div class="chart-row">
                            <div class="chart-row-label">{{ $label }}</div>
                            <div class="chart-track"><div class="chart-bar chart-bar-muted" style="width: {{ $barWidth($count, $maxSla) }}%"></div></div>
                            <div class="chart-row-value">{{ $count }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="app-card p-6">
                <h2 class="section-title">Tendencia de solicitudes</h2>
                <div class="trend-chart mt-5">
                    @foreach($ticketsCreatedByDay as $day)
                        <div class="trend-item">
                            <div class="trend-value">{{ $day['count'] }}</div>
                            <div class="trend-bar" style="height: {{ $barWidth($day['count'], $maxDay) }}%"></div>
                            <div class="trend-label">{{ $day['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="app-card p-6">
                <h2 class="section-title">Tipo de apoyo</h2>
                <div class="space-y-3 mt-4">
                    @foreach($ticketsByRequestType as $label => $count)
                        <div class="chart-row">
                            <div class="chart-row-label">{{ $label }}</div>
                            <div class="chart-track"><div class="chart-bar chart-bar-muted" style="width: {{ $barWidth($count, $maxRequestType) }}%"></div></div>
                            <div class="chart-row-value">{{ $count }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="app-card p-6">
                <h2 class="section-title">Áreas con más demanda</h2>
                <div class="space-y-3 mt-4">
                    @foreach($ticketsByCategory as $label => $count)
                        <div class="chart-row">
                            <div class="chart-row-label">{{ $label }}</div>
                            <div class="chart-track"><div class="chart-bar chart-bar-muted" style="width: {{ $barWidth($count, $maxCategory) }}%"></div></div>
                            <div class="chart-row-value">{{ $count }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="app-card p-6">
                <h2 class="section-title">Nivel de atención</h2>
                <div class="space-y-3 mt-4">
                    @foreach($ticketsByUserLevel as $label => $count)
                        <div class="chart-row">
                            <div class="chart-row-label">{{ $label }}</div>
                            <div class="chart-track"><div class="chart-bar chart-bar-muted" style="width: {{ $barWidth($count, $maxUserLevel) }}%"></div></div>
                            <div class="chart-row-value">{{ $count }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="app-card p-6">
            <h2 class="section-title">Carga de agentes</h2>
            <div class="space-y-4 mt-4">
                @forelse($agentsLoad as $agent)
                    <div class="agent-row">
                        <div>
                            <p class="font-semibold text-[var(--text-main)]">{{ $agent['name'] }}</p>
                            <p class="text-xs text-soft mt-1">{{ $agent['active_tickets'] }} activos · {{ $agent['resolved_tickets'] }} con solución · {{ $agent['overdue_tickets'] }} vencidos</p>
                        </div>
                        <div class="agent-row-chart">
                            <div class="chart-track"><div class="chart-bar" style="width: {{ $barWidth($agent['active_tickets'], $maxAgentLoad) }}%"></div></div>
                            <p class="text-xs text-soft mt-1">Prom. solución: {{ $agent['avg_resolution_hours'] }} h</p>
                        </div>
                    </div>
                @empty
                    <p class="text-soft text-sm">No hay agentes registrados.</p>
                @endforelse
            </div>
        </section>

        <section class="app-card p-6">
            <h2 class="section-title">Oportunidades de mejora</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                @foreach($opportunities as $item)
                    <div class="decision-card">
                        <p class="font-semibold text-[var(--text-main)]">{{ $item['title'] }}</p>
                        <p class="text-sm text-soft mt-1 leading-6">{{ $item['message'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </main>
</body>
</html>
