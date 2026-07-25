<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte ejecutivo mesa de ayuda</title>
    <style>
        @page { size: letter landscape; margin: 16px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #0f172a;
            font-size: 9px;
            line-height: 1.32;
        }
        h1, h2, h3, p { margin: 0; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; border-bottom: 2px solid #6d4aff; }
        .header-table td { border: 0; padding: 0 0 9px 0; vertical-align: top; }
        .title { font-size: 21px; font-weight: 800; color: #0f2850; }
        .subtitle { color: #52627a; margin-top: 3px; font-size: 9px; }
        .stamp { text-align: right; color: #52627a; font-size: 8px; }
        .page-section { margin-top: 8px; }
        .kpi-table, .layout-table { width: 100%; border-collapse: separate; border-spacing: 7px; margin-left: -7px; margin-right: -7px; }
        .kpi-card, .box {
            background: #f8fafc;
            border: 1px solid #dbe4f0;
            border-radius: 8px;
            padding: 8px;
            vertical-align: top;
        }
        .kpi-label { color: #52627a; font-size: 7px; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; }
        .kpi-value { font-size: 18px; font-weight: 800; color: #06152d; margin-top: 3px; }
        .kpi-help { color: #64748b; font-size: 7px; margin-top: 2px; }
        .section-title { font-size: 12px; font-weight: 800; color: #0f2850; margin-bottom: 5px; }
        .bar-row { margin-bottom: 5px; }
        .bar-meta { width: 100%; font-size: 8px; margin-bottom: 2px; clear: both; }
        .bar-label { float: left; max-width: 80%; white-space: nowrap; overflow: hidden; }
        .bar-count { float: right; font-weight: 700; }
        .clear { clear: both; }
        .bar-track { height: 6px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
        .bar-fill { height: 6px; border-radius: 999px; background: #0ea5e9; }
        .purple { background: #6d4aff; }
        .green { background: #16a34a; }
        .amber { background: #f59e0b; }
        .red { background: #dc2626; }
        .mini-table { width: 100%; border-collapse: collapse; }
        .mini-table th { text-align: left; color: #475569; font-size: 7px; text-transform: uppercase; padding: 5px; background: #eef2ff; border: 1px solid #e2e8f0; }
        .mini-table td { padding: 5px; border: 1px solid #e2e8f0; font-size: 8px; vertical-align: top; }
        .badge { display: inline-block; border-radius: 999px; padding: 2px 6px; background: #e0e7ff; color: #3730a3; font-size: 7px; font-weight: 700; }
        .note { color: #64748b; font-size: 8px; margin-top: 3px; }
        .footer { margin-top: 8px; padding-top: 6px; border-top: 1px solid #dbe4f0; color: #64748b; font-size: 7px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    @php
        $barWidth = function ($count, $max): int {
            $max = max((int) $max, 1);
            return max(3, min(100, (int) round(((int) $count / $max) * 100)));
        };
        $short = fn ($value, $length = 28) => \Illuminate\Support\Str::limit((string) $value, $length);
        $maxDay = max((int) ($ticketsCreatedByDay->max('count') ?? 1), 1);
        $maxAgent = max((int) ($agentsLoad->max('active_tickets') ?? 1), 1);
        $maxCategory = max((int) ($ticketsByCategory->max() ?? 1), 1);
        $maxType = max((int) ($ticketsByRequestType->max() ?? 1), 1);
        $maxStatus = max((int) ($ticketsByStatus->max() ?? 1), 1);
        $maxLevel = max((int) ($ticketsByUserLevel->max() ?? 1), 1);
        $maxPriority = max((int) ($ticketsByPriority->max() ?? 1), 1);
    @endphp

    <table class="header-table">
        <tr>
            <td style="width:70%;">
                <h1 class="title">Reporte ejecutivo de mesa de ayuda</h1>
                <p class="subtitle">Indicadores de operación, cumplimiento de tiempos, carga de agentes y oportunidades de mejora.</p>
            </td>
            <td style="width:30%;" class="stamp">
                Generado: {{ \App\Support\MexicoCityTime::dateTime($generatedAt ?? now()) }}<br>
                Zona horaria: CDMX
            </td>
        </tr>
    </table>

    <table class="kpi-table">
        <tr>
            <td class="kpi-card" style="width:16.66%;"><p class="kpi-label">Solicitudes activas</p><p class="kpi-value">{{ $activeTickets }}</p><p class="kpi-help">Casos abiertos en seguimiento.</p></td>
            <td class="kpi-card" style="width:16.66%;"><p class="kpi-label">Sin responsable</p><p class="kpi-value">{{ $unassignedTickets }}</p><p class="kpi-help">Requieren asignación.</p></td>
            <td class="kpi-card" style="width:16.66%;"><p class="kpi-label">Por vencer</p><p class="kpi-value">{{ $dueSoonTickets }}</p><p class="kpi-help">Atención preventiva.</p></td>
            <td class="kpi-card" style="width:16.66%;"><p class="kpi-label">Vencidas</p><p class="kpi-value">{{ $overdueTickets }}</p><p class="kpi-help">Fuera de tiempo objetivo.</p></td>
            <td class="kpi-card" style="width:16.66%;"><p class="kpi-label">Finalizadas</p><p class="kpi-value">{{ $closedTicketsCount ?? $closedTickets ?? 0 }}</p><p class="kpi-help">Solicitudes concluidas.</p></td>
            <td class="kpi-card" style="width:16.66%;"><p class="kpi-label">Satisfacción promedio</p><p class="kpi-value">{{ number_format($avgSatisfaction, 1) }}/5</p><p class="kpi-help">{{ $ratedTicketsCount }} evaluación(es).</p></td>
        </tr>
    </table>

    <table class="layout-table page-section">
        <tr>
            <td class="box" style="width:33.33%;">
                <h2 class="section-title">Cumplimiento de tiempos de atención</h2>
                <div class="bar-row">
                    <div class="bar-meta"><span class="bar-label">Primera respuesta</span><span class="bar-count">{{ $responseCompliance }}%</span><div class="clear"></div></div>
                    <div class="bar-track"><div class="bar-fill green" style="width: {{ $responseCompliance }}%;"></div></div>
                </div>
                <div class="bar-row">
                    <div class="bar-meta"><span class="bar-label">Solución</span><span class="bar-count">{{ $solutionCompliance }}%</span><div class="clear"></div></div>
                    <div class="bar-track"><div class="bar-fill purple" style="width: {{ $solutionCompliance }}%;"></div></div>
                </div>
                <p class="note">Prom. primera respuesta: {{ $avgFirstResponseHours }} h · Prom. solución: {{ $avgResolutionHours }} h</p>
            </td>
            <td class="box" style="width:33.33%;">
                <h2 class="section-title">Tendencia reciente</h2>
                @foreach($ticketsCreatedByDay->take(8) as $day)
                    <div class="bar-row">
                        <div class="bar-meta"><span class="bar-label">{{ data_get($day, 'label') }}</span><span class="bar-count">{{ data_get($day, 'count') }}</span><div class="clear"></div></div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ $barWidth(data_get($day, 'count'), $maxDay) }}%;"></div></div>
                    </div>
                @endforeach
            </td>
            <td class="box" style="width:33.33%;">
                <h2 class="section-title">Estado de solicitudes</h2>
                @foreach($ticketsByStatus->take(7) as $label => $count)
                    <div class="bar-row">
                        <div class="bar-meta"><span class="bar-label">{{ $short($label, 26) }}</span><span class="bar-count">{{ $count }}</span><div class="clear"></div></div>
                        <div class="bar-track"><div class="bar-fill purple" style="width: {{ $barWidth($count, $maxStatus) }}%;"></div></div>
                    </div>
                @endforeach
            </td>
        </tr>
    </table>

    <table class="layout-table page-section">
        <tr>
            <td class="box" style="width:25%;">
                <h2 class="section-title">Carga de agentes</h2>
                @foreach($agentsLoad->take(6) as $agent)
                    <div class="bar-row">
                        <div class="bar-meta"><span class="bar-label">{{ $short(data_get($agent, 'name'), 22) }}</span><span class="bar-count">{{ data_get($agent, 'active_tickets') }}</span><div class="clear"></div></div>
                        <div class="bar-track"><div class="bar-fill {{ data_get($agent, 'overdue_tickets', 0) > 0 ? 'red' : 'green' }}" style="width: {{ $barWidth(data_get($agent, 'active_tickets'), $maxAgent) }}%;"></div></div>
                    </div>
                @endforeach
            </td>
            <td class="box" style="width:25%;">
                <h2 class="section-title">Áreas con mayor demanda</h2>
                @foreach($ticketsByCategory->take(6) as $label => $count)
                    <div class="bar-row"><div class="bar-meta"><span class="bar-label">{{ $short($label, 24) }}</span><span class="bar-count">{{ $count }}</span><div class="clear"></div></div><div class="bar-track"><div class="bar-fill" style="width: {{ $barWidth($count, $maxCategory) }}%;"></div></div></div>
                @endforeach
            </td>
            <td class="box" style="width:25%;">
                <h2 class="section-title">Tipo de apoyo</h2>
                @foreach($ticketsByRequestType->take(6) as $label => $count)
                    <div class="bar-row"><div class="bar-meta"><span class="bar-label">{{ $short($label, 24) }}</span><span class="bar-count">{{ $count }}</span><div class="clear"></div></div><div class="bar-track"><div class="bar-fill amber" style="width: {{ $barWidth($count, $maxType) }}%;"></div></div></div>
                @endforeach
            </td>
            <td class="box" style="width:25%;">
                <h2 class="section-title">Nivel de atención</h2>
                @foreach($ticketsByUserLevel->take(6) as $label => $count)
                    <div class="bar-row"><div class="bar-meta"><span class="bar-label">{{ $short($label, 24) }}</span><span class="bar-count">{{ $count }}</span><div class="clear"></div></div><div class="bar-track"><div class="bar-fill purple" style="width: {{ $barWidth($count, $maxLevel) }}%;"></div></div></div>
                @endforeach
            </td>
        </tr>
    </table>

    <table class="layout-table page-section">
        <tr>
            <td class="box" style="width:25%;">
                <h2 class="section-title">Prioridad interna</h2>
                @foreach($ticketsByPriority->take(6) as $label => $count)
                    <div class="bar-row"><div class="bar-meta"><span class="bar-label">{{ $short($label, 24) }}</span><span class="bar-count">{{ $count }}</span><div class="clear"></div></div><div class="bar-track"><div class="bar-fill {{ mb_strtolower($label) === 'alta' ? 'red' : 'purple' }}" style="width: {{ $barWidth($count, $maxPriority) }}%;"></div></div></div>
                @endforeach
            </td>
            <td class="box" style="width:75%;">
                <h2 class="section-title">Atención prioritaria</h2>
                <table class="mini-table">
                    <thead><tr><th>Folio</th><th>Asunto</th><th>Solicitante</th><th>Estado</th><th>Responsable</th></tr></thead>
                    <tbody>
                        @forelse($topRiskTickets->take(6) as $ticket)
                            <tr>
                                <td>{{ $ticket->folio }}</td>
                                <td>{{ $short($ticket->subject, 36) }}</td>
                                <td>{{ $ticket->creator?->name ?? 'Sin dato' }}</td>
                                <td><span class="badge">{{ $ticket->status?->name ?? 'Sin estado' }}</span></td>
                                <td>{{ $ticket->assignee?->name ?? 'Sin responsable' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No hay casos prioritarios pendientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <div class="page-break"></div>

    <table class="header-table">
        <tr>
            <td style="width:70%;"><h1 class="title">Detalle operativo</h1><p class="subtitle">Carga, riesgos y oportunidades detectadas para seguimiento administrativo.</p></td>
            <td style="width:30%;" class="stamp">Reporte ejecutivo<br>{{ \App\Support\MexicoCityTime::date($generatedAt ?? now()) }}</td>
        </tr>
    </table>

    <table class="layout-table">
    <tr>
        <td class="box" style="width:100%;">
            <h2 class="section-title">Desempeño de agentes</h2>

            <table class="mini-table">
                <thead>
                    <tr>
                        <th>Agente</th>
                        <th>Activos</th>
                        <th>Vencidos</th>
                        <th>Solución registrada</th>
                        <th>Finalizados</th>
                        <th>Tiempo prom.</th>
                        <th>Resp.</th>
                        <th>Sol.</th>
                        <th>Satisfacción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agentsLoad->take(10) as $agent)
                        <tr>
                            <td>{{ data_get($agent, 'name', 'Sin nombre') }}</td>
                            <td>{{ data_get($agent, 'active_tickets', 0) }}</td>
                            <td>{{ data_get($agent, 'overdue_tickets', 0) }}</td>
                            <td>{{ data_get($agent, 'resolved_tickets', 0) }}</td>
                            <td>{{ data_get($agent, 'closed_tickets', 0) }}</td>
                            <td>{{ number_format((float) data_get($agent, 'avg_resolution_hours', 0), 1) }} h</td>
                            <td>{{ number_format((float) data_get($agent, 'response_compliance', 0), 1) }}%</td>
                            <td>{{ number_format((float) data_get($agent, 'solution_compliance', 0), 1) }}%</td>
                            <td>
                                {{ number_format((float) data_get($agent, 'avg_satisfaction', data_get($agent, 'satisfaction_average', 0)), 1) }}/5
                                <br>
                                <span style="font-size:7px; color:#64748b;">
                                    {{ data_get($agent, 'satisfaction_count', 0) }} eval.
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">No hay agentes con actividad registrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </td>
    </tr>
</table>

<table class="layout-table page-section">
    <tr>
        <td class="box" style="width:100%;">
            <h2 class="section-title">Solicitudes de alto nivel pendientes</h2>

            <table class="mini-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Solicitante</th>
                        <th>Estado</th>
                        <th>Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($highLevelPendingTickets->take(8) as $ticket)
                        <tr>
                            <td>{{ $ticket->folio }}</td>
                            <td>{{ $ticket->creator?->name ?? 'Sin dato' }}</td>
                            <td>{{ $ticket->status?->name ?? 'Sin estado' }}</td>
                            <td>{{ $ticket->assignee?->name ?? 'Sin responsable' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No hay solicitudes de alto nivel pendientes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </td>
    </tr>
</table>

    <table class="layout-table page-section">
        <tr>
            <td class="box" style="width:100%;">
                <h2 class="section-title">Oportunidades de mejora</h2>
                <table class="mini-table">
                    <tbody>
                        @forelse($opportunities->take(8) as $item)
                            <tr><td><strong>{{ data_get($item, 'title') }}</strong><br>{{ data_get($item, 'message') }}</td></tr>
                        @empty
                            <tr><td>No se detectaron riesgos relevantes en este periodo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">Reporte generado automáticamente por el sistema Helpdesk. La información corresponde a los registros disponibles al momento de emisión.</div>
</body>
</html>
