<x-app-layout>
    @php
        $currentUser = auth()->user();
        $isRequester = $currentUser->isUserRole();
        $isAgent = $currentUser->isAgent();
        $isAdmin = $currentUser->isAdmin();

        $selectedView = $viewMode ?? request('view', 'active');

        $pageTitle = $isRequester
            ? ($selectedView === 'history' ? 'Solicitudes finalizadas' : 'Mis solicitudes')
            : ($isAgent
                ? ($selectedView === 'history' ? 'Historial de casos' : 'Bandeja de trabajo')
                : ($selectedView === 'history' ? 'Historial administrativo' : 'Supervisión activa'));

        $pageSubtitle = $isRequester
            ? ($selectedView === 'history'
                ? 'Consulta solicitudes que ya fueron finalizadas o canceladas.'
                : 'Consulta el avance de tus solicitudes abiertas o en seguimiento.')
            : ($isAgent
                ? ($selectedView === 'history'
                    ? 'Consulta casos atendidos previamente y reabre cuando sea necesario.'
                    : 'Atiende tus casos asignados y toma casos disponibles.')
                : ($selectedView === 'history'
                    ? 'Consulta casos finalizados, soluciones registradas y cancelaciones.'
                    : 'Supervisa asignación, prioridad, tiempos y carga operativa.'));

        $selectedOrder = request('order', $isRequester ? 'recientes' : 'cola');
        $selectedStatus = request('status');
        $selectedStatusGroup = request('status_group');
        $selectedUserLevel = request('user_level');
        $selectedAssignment = request('assignment');
        $selectedPriority = request('priority');
        $selectedCategory = request('category');
        $selectedRequestType = request('request_type');
        $selectedTime = request('time_status', request('sla'));
        $selectedAgent = request('agent_id');
        $terminalSlugs = ['resuelto', 'cerrado', 'cancelado'];
        $requesterClosedSlugs = ['cerrado', 'cancelado'];

        $statusLabel = function ($status, bool $forUser = false): string {
            $slug = strtolower((string) ($status?->slug ?? ''));

            if ($forUser) {
                return match ($slug) {
                    'nuevo' => 'Recibida',
                    'en-revision', 'asignado', 'reabierto' => 'En revisión',
                    'en-proceso' => 'En atención',
                    'en-espera-usuario' => 'Requiere tu respuesta',
                    'en-espera-proveedor' => 'En seguimiento',
                    'resuelto' => 'Solución registrada',
                    'cerrado' => 'Finalizada',
                    'cancelado' => 'Cancelada',
                    default => 'En seguimiento',
                };
            }

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

        $statusForUser = fn ($status): string => $statusLabel($status, true);
        $statusForOperator = fn ($status): string => $statusLabel($status, false);

        $statusBadgeForUser = function ($status): string {
            $slug = strtolower((string) ($status?->slug ?? ''));
            if ($slug === 'en-espera-usuario') return 'badge-amber';
            if ($slug === 'resuelto' || $slug === 'cerrado') return 'badge-emerald';
            if ($slug === 'cancelado') return 'badge-slate';
            return 'badge-blue';
        };

        $badgeForPriority = function ($priorityName): string {
            $value = strtolower((string) $priorityName);
            if (str_contains($value, 'crítica') || str_contains($value, 'critica') || str_contains($value, 'urgente') || str_contains($value, 'alta')) return 'badge-rose';
            if (str_contains($value, 'media')) return 'badge-amber';
            if (str_contains($value, 'baja')) return 'badge-emerald';
            return 'badge-slate';
        };

        $badgeForAttention = function ($level): string {
            if ($level === 'director_general') return 'badge-rose';
            if ($level === 'subdirector') return 'badge-amber';
            if ($level === 'gerente') return 'badge-blue';
            return 'badge-slate';
        };

        $formatRemainingTime = function ($dueAt): string {
            return \App\Support\MexicoCityTime::remaining($dueAt);
        };

        $statusClassForOperator = function ($status): string {
            $slug = strtolower((string) ($status?->slug ?? ''));
            if (in_array($slug, ['cerrado', 'resuelto'], true)) return 'badge-emerald';
            if ($slug === 'en-espera-usuario' || $slug === 'en-espera-proveedor') return 'badge-amber';
            if ($slug === 'cancelado') return 'badge-slate';
            return 'badge-blue';
        };
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">{{ $pageTitle }}</h1>
                <p class="page-subtitle">{{ $pageSubtitle }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('dashboard') }}" class="app-btn-secondary">{{ $isRequester ? 'Inicio' : 'Dashboard' }}</a>
                @if($isRequester || $isAdmin)
                    <a href="{{ route('tickets.create') }}" class="app-btn-primary">{{ $isRequester ? 'Nueva solicitud' : 'Nuevo ticket' }}</a>
                @endif
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

            @if($isRequester)
                <nav class="role-work-tabs" aria-label="Navegación de solicitudes">
                    <a href="{{ route('tickets.index', array_merge(request()->except(['view', 'page']), ['view' => 'active', 'order' => request('order', 'recientes')])) }}" class="role-work-tab {{ $selectedView === 'active' ? 'active' : '' }}">
                        Abiertas o en seguimiento
                        @if(!is_null($activeTabCount))<span>{{ $activeTabCount }}</span>@endif
                    </a>
                    <a href="{{ route('tickets.index', array_merge(request()->except(['view', 'page']), ['view' => 'history', 'order' => request('order', 'recientes')])) }}" class="role-work-tab {{ $selectedView === 'history' ? 'active' : '' }}">
                        Finalizadas
                        @if(!is_null($historyTabCount))<span>{{ $historyTabCount }}</span>@endif
                    </a>
                </nav>
            @endif

            @if($isAgent || $isAdmin)
                <nav class="role-work-tabs" aria-label="Navegación de trabajo">
                    <a href="{{ route('dashboard') }}" class="role-work-tab">Dashboard</a>
                    <a href="{{ route('tickets.index', array_merge(request()->except(['view', 'page']), ['view' => 'active', 'order' => request('order', 'cola')])) }}" class="role-work-tab {{ $selectedView === 'active' ? 'active' : '' }}">
                        {{ $isAgent ? 'Bandeja' : 'Supervisión activa' }}
                        @if(!is_null($activeTabCount))<span>{{ $activeTabCount }}</span>@endif
                    </a>
                    <a href="{{ route('tickets.index', array_merge(request()->except(['view', 'page']), ['view' => 'history', 'order' => request('order', 'recientes')])) }}" class="role-work-tab {{ $selectedView === 'history' ? 'active' : '' }}">
                        Historial
                        @if(!is_null($historyTabCount))<span>{{ $historyTabCount }}</span>@endif
                    </a>
                </nav>
            @endif

            @if($isAgent && $selectedView !== 'history')
                <div class="app-notice">
                    <p class="font-semibold text-[var(--text-main)]">Alcance de tu bandeja</p>
                    <p class="text-soft text-sm mt-1">Aquí aparecen tus casos activos asignados y los casos disponibles para tomar. Los casos finalizados pasan al historial.</p>
                </div>
            @elseif($isAgent && $selectedView === 'history')
                <div class="app-notice">
                    <p class="font-semibold text-[var(--text-main)]">Historial de atención</p>
                    <p class="text-soft text-sm mt-1">Aquí puedes consultar casos con solución registrada, finalizados o cancelados. Si requieren seguimiento adicional, abre el detalle para reabrirlos.</p>
                </div>
            @elseif($isRequester && $selectedView === 'history')
                <div class="app-notice">
                    <p class="font-semibold text-[var(--text-main)]">Solicitudes finalizadas</p>
                    <p class="text-soft text-sm mt-1">Las solicitudes cerradas quedan solo para consulta. Ya no admiten mensajes, archivos ni revisión adicional. Si necesitas apoyo de nuevo, registra una nueva solicitud.</p>
                </div>
            @endif

            @if($isRequester)
                <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6" aria-label="Resumen de solicitudes">
                    <div class="metric-card"><p class="metric-label">Solicitudes</p><p class="metric-value">{{ $summary['total'] ?? $tickets->total() }}</p></div>
                    <div class="metric-card"><p class="metric-label">En seguimiento</p><p class="metric-value">{{ $summary['user_followup'] ?? 0 }}</p></div>
                    <div class="metric-card"><p class="metric-label">Requieren tu respuesta</p><p class="metric-value">{{ $summary['user_waiting'] ?? 0 }}</p></div>
                    <div class="metric-card"><p class="metric-label">{{ $selectedView === 'history' ? 'Cerradas' : 'Por confirmar' }}</p><p class="metric-value">{{ $selectedView === 'history' ? ($summary['user_finished'] ?? 0) : ($summary['user_solution'] ?? 0) }}</p></div>
                </section>
            @elseif($isAgent)
                @if($selectedView === 'history')
                    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6" aria-label="Resumen de historial">
                        <div class="metric-card"><p class="metric-label">En historial</p><p class="metric-value">{{ $summary['total'] ?? 0 }}</p></div>
                        <div class="metric-card"><p class="metric-label">Solución registrada</p><p class="metric-value">{{ $summary['history_solution'] ?? 0 }}</p></div>
                        <div class="metric-card"><p class="metric-label">Finalizadas</p><p class="metric-value">{{ $summary['history_closed'] ?? 0 }}</p></div>
                        <div class="metric-card"><p class="metric-label">Canceladas</p><p class="metric-value">{{ $summary['history_cancelled'] ?? 0 }}</p></div>
                    </section>
                @else
                    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6" aria-label="Resumen operativo">
                        <div class="metric-card"><p class="metric-label">Mis casos</p><p class="metric-value">{{ $summary['mine'] ?? 0 }}</p></div>
                        <div class="metric-card"><p class="metric-label">Disponibles</p><p class="metric-value">{{ $summary['unassigned'] ?? 0 }}</p></div>
                        <div class="metric-card"><p class="metric-label">Por vencer</p><p class="metric-value">{{ $summary['due_soon'] ?? 0 }}</p></div>
                        <div class="metric-card"><p class="metric-label">Vencidos</p><p class="metric-value">{{ $summary['overdue'] ?? 0 }}</p></div>
                    </section>
                @endif
            @else
                @if($selectedView === 'history')
                    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6" aria-label="Resumen de historial administrativo">
                        <div class="metric-card"><p class="metric-label">En historial</p><p class="metric-value">{{ $summary['total'] ?? 0 }}</p></div>
                        <div class="metric-card"><p class="metric-label">Solución registrada</p><p class="metric-value">{{ $summary['history_solution'] ?? 0 }}</p></div>
                        <div class="metric-card"><p class="metric-label">Finalizadas</p><p class="metric-value">{{ $summary['history_closed'] ?? 0 }}</p></div>
                        <div class="metric-card"><p class="metric-label">Canceladas</p><p class="metric-value">{{ $summary['history_cancelled'] ?? 0 }}</p></div>
                    </section>
                @else
                    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6" aria-label="Resumen administrativo">
                        <div class="metric-card"><p class="metric-label">Activos</p><p class="metric-value">{{ $summary['active'] ?? 0 }}</p></div>
                        <div class="metric-card"><p class="metric-label">Sin responsable</p><p class="metric-value">{{ $summary['unassigned'] ?? 0 }}</p></div>
                        <div class="metric-card"><p class="metric-label">Por vencer</p><p class="metric-value">{{ $summary['due_soon'] ?? 0 }}</p></div>
                        <div class="metric-card"><p class="metric-label">Vencidos</p><p class="metric-value">{{ $summary['overdue'] ?? 0 }}</p></div>
                    </section>
                @endif
            @endif

            <section class="app-card p-6" aria-label="Filtros">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-5">
                    <div>
                        <h2 class="section-title">Buscar y filtrar</h2>
                        <p class="text-soft text-sm">
                            {{ $isRequester ? 'Encuentra una solicitud por folio, asunto, estado o área.' : 'Filtra la bandeja por estado, prioridad, tiempo, responsable o nivel de atención.' }}
                        </p>
                    </div>
                    <a href="{{ ($isAgent || $isAdmin) ? route('tickets.index', ['view' => $selectedView]) : route('tickets.index') }}" class="app-btn-secondary">Limpiar</a>
                </div>

                <form method="GET" action="{{ route('tickets.index') }}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    @if($isAgent || $isAdmin)
                        <input type="hidden" name="view" value="{{ $selectedView }}">
                    @endif
                    <div class="xl:col-span-2">
                        <label for="q" class="form-label">Buscar</label>
                        <input id="q" name="q" type="text" value="{{ request('q') }}" placeholder="{{ $isRequester ? 'Folio, asunto o descripción' : 'Folio, asunto, solicitante o responsable' }}">
                    </div>

                    <div>
                        <label for="order" class="form-label">Orden</label>
                        <select id="order" name="order">
                            <option value="recientes" {{ $selectedOrder === 'recientes' ? 'selected' : '' }}>Más recientes</option>
                            @if(!$isRequester)
                                <option value="cola" {{ $selectedOrder === 'cola' ? 'selected' : '' }}>Cola recomendada</option>
                                <option value="criticidad" {{ $selectedOrder === 'criticidad' ? 'selected' : '' }}>Mayor criticidad</option>
                            @endif
                            <option value="antiguos" {{ $selectedOrder === 'antiguos' ? 'selected' : '' }}>Más antiguos</option>
                        </select>
                    </div>

                    <div>
                        @if($isRequester)
                            <label for="status_group" class="form-label">Estado</label>
                            <select id="status_group" name="status_group">
                                <option value="">Todos</option>
                                <option value="seguimiento" {{ $selectedStatusGroup === 'seguimiento' ? 'selected' : '' }}>En curso</option>
                                <option value="requiere_respuesta" {{ $selectedStatusGroup === 'requiere_respuesta' ? 'selected' : '' }}>Requiere tu respuesta</option>
                                <option value="solucion_registrada" {{ $selectedStatusGroup === 'solucion_registrada' ? 'selected' : '' }}>Solución registrada</option>
                                <option value="finalizadas" {{ $selectedStatusGroup === 'finalizadas' ? 'selected' : '' }}>Finalizadas</option>
                                <option value="canceladas" {{ $selectedStatusGroup === 'canceladas' ? 'selected' : '' }}>Canceladas</option>
                            </select>
                        @else
                            <label for="status" class="form-label">Estado</label>
                            <select id="status" name="status">
                                <option value="">Todos</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->id }}" {{ (string) $selectedStatus === (string) $status->id ? 'selected' : '' }}>{{ $statusForOperator($status) }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <div>
                        <label for="category" class="form-label">Área relacionada</label>
                        <select id="category" name="category">
                            <option value="">Todas</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ (string) $selectedCategory === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="request_type" class="form-label">Tipo de apoyo</label>
                        <select id="request_type" name="request_type">
                            <option value="">Todos</option>
                            @foreach($requestTypes as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}" {{ $selectedRequestType === $typeKey ? 'selected' : '' }}>{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if(!$isRequester)
                        <div>
                            <label for="priority" class="form-label">Prioridad</label>
                            <select id="priority" name="priority">
                                <option value="">Todas</option>
                                <option value="sin_clasificar" {{ $selectedPriority === 'sin_clasificar' ? 'selected' : '' }}>Sin clasificar</option>
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority->id }}" {{ (string) $selectedPriority === (string) $priority->id ? 'selected' : '' }}>{{ $priority->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="assignment" class="form-label">Bandeja</label>
                            <select id="assignment" name="assignment">
                                <option value="">Todo lo visible</option>
                                <option value="mis_tickets" {{ $selectedAssignment === 'mis_tickets' ? 'selected' : '' }}>Mis casos</option>
                                <option value="sin_asignar" {{ $selectedAssignment === 'sin_asignar' ? 'selected' : '' }}>Disponibles</option>
                                @if($isAdmin)
                                    <option value="asignados" {{ $selectedAssignment === 'asignados' ? 'selected' : '' }}>Asignados</option>
                                @endif
                            </select>
                        </div>

                        <div>
                            <label for="time_status" class="form-label">Tiempo</label>
                            <select id="time_status" name="time_status">
                                <option value="">Todos</option>
                                <option value="vencido" {{ $selectedTime === 'vencido' ? 'selected' : '' }}>Vencidos</option>
                                <option value="por_vencer" {{ $selectedTime === 'por_vencer' ? 'selected' : '' }}>Por vencer</option>
                                <option value="sin_clasificar" {{ $selectedTime === 'sin_clasificar' ? 'selected' : '' }}>Sin clasificación</option>
                            </select>
                        </div>

                        <div>
                            <label for="user_level" class="form-label">Nivel de atención</label>
                            <select id="user_level" name="user_level">
                                <option value="">Todos</option>
                                @foreach($attentionLevels as $levelKey => $levelData)
                                    <option value="{{ $levelKey }}" {{ $selectedUserLevel === $levelKey ? 'selected' : '' }}>{{ $levelData['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($isAdmin)
                        <div>
                            <label for="agent_id" class="form-label">Responsable</label>
                            <select id="agent_id" name="agent_id">
                                <option value="">Todos</option>
                                <option value="sin_asignar" {{ $selectedAgent === 'sin_asignar' ? 'selected' : '' }}>Sin responsable</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" {{ (string) $selectedAgent === (string) $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="flex items-end xl:col-span-4">
                        <button type="submit" class="app-btn-primary w-full md:w-auto">Aplicar filtros</button>
                    </div>
                </form>
            </section>

            <section class="app-card p-0 overflow-hidden" aria-label="Listado de tickets">
                <div class="flex flex-col gap-3 border-b border-[var(--border-soft)] px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="section-title">{{ $isRequester ? ($selectedView === 'history' ? 'Solicitudes finalizadas' : 'Solicitudes abiertas') : ($selectedView === 'history' ? 'Casos en historial' : ($isAgent ? 'Casos en bandeja' : 'Tickets activos')) }}</h2>
                        <p class="text-soft text-sm">{{ $isRequester ? ($selectedView === 'history' ? 'Consulta el registro de solicitudes que ya fueron cerradas.' : 'Se muestran primero tus solicitudes que aún requieren seguimiento.') : ($selectedView === 'history' ? 'Casos que ya no forman parte de la atención diaria.' : 'Se muestran primero los casos que requieren atención o revisión.') }}</p>
                    </div>
                    <span class="app-badge badge-slate">{{ $tickets->total() }} resultado{{ $tickets->total() === 1 ? '' : 's' }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--border-soft)] bg-slate-50 text-left text-[var(--text-soft)]">
                                @if($isRequester)
                                    <th class="px-6 py-4 font-semibold">Solicitud</th>
                                    <th class="px-6 py-4 font-semibold">Estado</th>
                                    <th class="px-6 py-4 font-semibold">Área</th>
                                    <th class="px-6 py-4 font-semibold">Tiempo de respuesta</th>
                                    <th class="px-6 py-4 font-semibold">Actualización</th>
                                    <th class="px-6 py-4 font-semibold text-right">Acción</th>
                                @else
                                    <th class="px-6 py-4 font-semibold">Caso</th>
                                    <th class="px-6 py-4 font-semibold">Solicitante</th>
                                    <th class="px-6 py-4 font-semibold">Estado</th>
                                    <th class="px-6 py-4 font-semibold">Prioridad</th>
                                    <th class="px-6 py-4 font-semibold">Tiempo</th>
                                    <th class="px-6 py-4 font-semibold">Responsable</th>
                                    <th class="px-6 py-4 font-semibold text-right">Acción</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tickets as $ticket)
                                @php
                                    $priorityName = $ticket->priorityLabel();
                                    $priorityClass = $badgeForPriority($priorityName);
                                    $attentionClass = $badgeForAttention($ticket->creator?->position_level ?? 'operativo');
                                    $responseLabel = $ticket->first_response_due_at ? $formatRemainingTime($ticket->first_response_due_at) : 'Pendiente';
                                    $responseClass = $responseLabel === 'Vencido' ? 'badge-rose' : ($ticket->first_response_due_at ? 'badge-blue' : 'badge-slate');
                                    if ($ticket->first_responded_at || $ticket->resolved_at || $ticket->closed_at) {
                                        $responseLabel = 'Con actualización';
                                        $responseClass = 'badge-emerald';
                                    }
                                    $resolutionLabel = $ticket->resolution_due_at ? $formatRemainingTime($ticket->resolution_due_at) : 'Sin tiempo';
                                    $resolutionClass = $resolutionLabel === 'Vencido' ? 'badge-rose' : ($ticket->resolution_due_at ? 'badge-blue' : 'badge-slate');
                                    if ($ticket->resolved_at || $ticket->closed_at) {
                                        $resolutionLabel = $ticket->closed_at ? 'Finalizada' : 'Solución registrada';
                                        $resolutionClass = 'badge-emerald';
                                    }
                                    $rowStatusSlug = strtolower((string) ($ticket->status?->slug ?? ''));
                                    $isTerminalRow = in_array($rowStatusSlug, $terminalSlugs, true);
                                    $operatorActionLabel = $isTerminalRow ? ($isAgent ? 'Ver / reabrir' : 'Revisar historial') : ($isAgent ? 'Atender' : 'Revisar');
                                @endphp
                                <tr class="border-b border-[var(--border-soft)] transition hover:bg-slate-50">
                                    @if($isRequester)
                                        <td class="px-6 py-4 align-top">
                                            <p class="font-semibold text-[var(--text-main)]">{{ $ticket->subject }}</p>
                                            <p class="text-xs text-soft mt-1">{{ $ticket->folio }} · {{ \App\Support\MexicoCityTime::dateTime($ticket->created_at) }}</p>
                                            <p class="mt-1 max-w-md text-xs text-soft">{{ \Illuminate\Support\Str::limit($ticket->description, 90) }}</p>
                                        </td>
                                        <td class="px-6 py-4 align-top"><span class="app-badge {{ $statusBadgeForUser($ticket->status) }}">{{ $statusForUser($ticket->status) }}</span></td>
                                        <td class="px-6 py-4 align-top text-soft">{{ $ticket->category->name ?? 'Sin área' }}</td>
                                        <td class="px-6 py-4 align-top"><span class="app-badge {{ $responseClass }}">{{ $responseLabel }}</span></td>
                                        <td class="px-6 py-4 align-top text-soft">{{ \App\Support\MexicoCityTime::dateTime($ticket->updated_at) }}</td>
                                        <td class="px-6 py-4 align-top text-right"><a href="{{ route('tickets.show', $ticket) }}" class="app-btn-secondary">{{ $selectedView === 'history' ? 'Ver resumen' : 'Ver seguimiento' }}</a></td>
                                    @else
                                        <td class="px-6 py-4 align-top">
                                            <p class="font-semibold text-[var(--text-main)]">{{ $ticket->folio }}</p>
                                            <p class="font-medium text-[var(--text-main)] mt-1">{{ $ticket->subject }}</p>
                                            <p class="mt-1 text-xs text-soft">{{ $ticket->category->name ?? 'Sin área' }} · {{ $ticket->requestTypeLabel() }}</p>
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            <p class="font-medium text-[var(--text-main)]">{{ $ticket->creator->name ?? 'Sin solicitante' }}</p>
                                            <p class="text-xs text-soft mt-1">{{ $ticket->creator->email ?? '' }}</p>
                                            <span class="app-badge {{ $attentionClass }} mt-2">{{ $ticket->creator?->attentionLabel() ?? 'Operativo' }}</span>
                                        </td>
                                        <td class="px-6 py-4 align-top"><span class="app-badge {{ $statusClassForOperator($ticket->status) }}">{{ $statusForOperator($ticket->status) }}</span></td>
                                        <td class="px-6 py-4 align-top"><span class="app-badge {{ $priorityClass }}">{{ $priorityName }}</span></td>
                                        <td class="px-6 py-4 align-top">
                                            <span class="app-badge {{ $resolutionClass }}">{{ $resolutionLabel }}</span>
                                            <p class="text-xs text-soft mt-1">Solución</p>
                                        </td>
                                        <td class="px-6 py-4 align-top text-soft">{{ $ticket->assignee->name ?? 'Disponible' }}</td>
                                        <td class="px-6 py-4 align-top text-right"><a href="{{ route('tickets.show', $ticket) }}" class="{{ $isTerminalRow ? 'app-btn-secondary' : 'app-btn-primary' }}">{{ $operatorActionLabel }}</a></td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isRequester ? 6 : 7 }}" class="px-6 py-12 text-center text-soft">
                                        {{ $isRequester ? ($selectedView === 'history' ? 'Aún no tienes solicitudes finalizadas.' : 'No tienes solicitudes abiertas con estos criterios.') : 'No hay casos con estos criterios.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrap border-t border-[var(--border-soft)] px-6 py-4">
                    {{ $tickets->links() }}
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
