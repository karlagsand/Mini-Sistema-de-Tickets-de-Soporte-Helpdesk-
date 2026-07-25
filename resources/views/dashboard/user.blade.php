<x-app-layout>
    @php
        $statusForUser = function ($status): string {
            $slug = strtolower((string) ($status?->slug ?? ''));
            return match ($slug) {
                'nuevo' => 'Recibida',
                'asignado', 'en-revision', 'reabierto' => 'En revisión',
                'en-proceso' => 'En atención',
                'en-espera-proveedor' => 'En seguimiento',
                'en-espera-usuario' => 'Requiere tu respuesta',
                'resuelto' => 'Solución registrada',
                'cerrado' => 'Finalizada',
                'cancelado' => 'Cancelada',
                default => 'En seguimiento',
            };
        };
        $statusClass = function ($status): string {
            $slug = strtolower((string) ($status?->slug ?? ''));
            if ($slug === 'en-espera-usuario') return 'badge-amber';
            if ($slug === 'resuelto' || $slug === 'cerrado') return 'badge-emerald';
            if ($slug === 'cancelado') return 'badge-slate';
            return 'badge-blue';
        };

        $updatesCount = $updates->count();
        $requiresResponseCount = $updates->where('label', 'Responder')->count();
        $solutionReviewCount = $updates->where('label', 'Revisar solución')->count();
        $heroTitle = $updatesCount > 0
            ? 'Tienes novedades en tus solicitudes'
            : 'Tu seguimiento está al día';
        $heroMessage = $updatesCount > 0
            ? 'Revisa las actualizaciones recientes para continuar el seguimiento de tus solicitudes.'
            : 'Cuando una solicitud tenga avances o requiera tu atención, la verás primero aquí.';
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Inicio</h1>
                <p class="page-subtitle">Consulta novedades y seguimiento de tus solicitudes.</p>
            </div>
            <a href="{{ route('tickets.create') }}" class="app-btn-primary">Nueva solicitud</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap space-y-6">
            @include('partials.internal-notifications')

            <section class="user-hero-panel" aria-label="Resumen de seguimiento">
                <div>
                    <p class="eyebrow text-white/80">Hola, {{ auth()->user()->name }}</p>
                    <h2>{{ $heroTitle }}</h2>
                    <p>{{ $heroMessage }}</p>
                    <div class="hero-actions">
                        <a href="{{ route('tickets.create') }}" class="hero-action">Nueva solicitud</a>
                        <a href="{{ route('tickets.index') }}" class="hero-action hero-action-soft">Ver seguimiento</a>
                    </div>
                </div>

                <div class="user-hero-metrics">
                    <div>
                        <strong>{{ $updatesCount }}</strong>
                        <span>Novedades</span>
                    </div>
                    <div>
                        <strong>{{ $requiresResponseCount }}</strong>
                        <span>Por responder</span>
                    </div>
                    <div>
                        <strong>{{ $solutionReviewCount }}</strong>
                        <span>Por confirmar</span>
                    </div>
                    <div>
                        <strong>{{ $activeTickets }}</strong>
                        <span>En seguimiento</span>
                    </div>
                </div>
            </section>

            <section class="app-card p-6 md:p-7">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="eyebrow">Novedades</p>
                        <h2 class="text-2xl font-bold text-[var(--text-main)] mt-1">Actualizaciones importantes</h2>
                        <p class="text-soft mt-2">Revisa solo las solicitudes que requieren atención o tienen avances recientes.</p>
                    </div>
                    <a href="{{ route('tickets.index') }}" class="app-btn-secondary">Ver mis solicitudes</a>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse($updates as $update)
                        <a href="{{ route('tickets.show', $update['ticket']) }}" class="notification-card notification-card-user">
                            <div class="notification-dot"></div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <p class="font-semibold text-[var(--text-main)]">{{ $update['title'] }}</p>
                                        <p class="text-sm text-soft mt-1 leading-6">{{ $update['message'] }}</p>
                                        <p class="text-xs text-soft mt-2">{{ $update['ticket']->folio }} · {{ $update['ticket']->subject }}</p>
                                    </div>
                                    <span class="app-badge badge-blue shrink-0">{{ $update['label'] }}</span>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="empty-state py-8">
                            <p class="font-semibold text-[var(--text-main)]">No tienes novedades pendientes.</p>
                            <p class="text-soft text-sm mt-1">Cuando una solicitud tenga respuesta o requiera información, aparecerá en esta sección.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                <div class="metric-card"><p class="metric-label">Solicitudes</p><p class="metric-value">{{ $totalTickets }}</p><p class="metric-note">Registradas en total.</p></div>
                <div class="metric-card"><p class="metric-label">En seguimiento</p><p class="metric-value">{{ $activeTickets }}</p><p class="metric-note">Aún no finalizan.</p></div>
                <div class="metric-card"><p class="metric-label">Solución registrada</p><p class="metric-value">{{ $resolvedTickets }}</p><p class="metric-note">Pendientes de confirmar.</p></div>
                <div class="metric-card"><p class="metric-label">Finalizadas</p><p class="metric-value">{{ $closedTickets }}</p><p class="metric-note">Concluidas.</p></div>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
                <div class="xl:col-span-2 app-card p-6">
                    <div class="flex items-center justify-between gap-4 mb-5">
                        <div>
                            <h2 class="section-title">Solicitudes recientes</h2>
                            <p class="text-soft text-sm">Resumen rápido del estado de tus últimos registros.</p>
                        </div>
                        <a href="{{ route('tickets.index') }}" class="app-btn-secondary">Ver todas</a>
                    </div>

                    <div class="space-y-4">
                        @forelse($recentTickets as $ticket)
                            <a href="{{ route('tickets.show', $ticket) }}" class="block info-panel hover:bg-slate-50 transition">
                                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <p class="font-semibold text-[var(--text-main)]">{{ $ticket->subject }}</p>
                                        <p class="text-xs text-soft mt-1">{{ $ticket->folio }} · Registro: {{ \App\Support\MexicoCityTime::dateTime($ticket->created_at) }}</p>
                                        <p class="text-sm text-soft mt-2">{{ $ticket->category->name ?? 'Sin área relacionada' }}</p>
                                    </div>
                                    <span class="app-badge {{ $statusClass($ticket->status) }}">{{ $statusForUser($ticket->status) }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="empty-state">
                                <p class="font-semibold text-[var(--text-main)]">Aún no tienes solicitudes.</p>
                                <p class="text-soft text-sm mt-1">Cuando registres una, aparecerá aquí.</p>
                                <a href="{{ route('tickets.create') }}" class="app-btn-primary mt-4">Registrar solicitud</a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <aside class="app-card p-6 space-y-4">
                    <h2 class="section-title">Acciones rápidas</h2>
                    <a href="{{ route('tickets.create') }}" class="app-btn-primary w-full">Nueva solicitud</a>
                    <a href="{{ route('tickets.index') }}" class="app-btn-secondary w-full">Consultar seguimiento</a>
                    <div class="info-panel">
                        <p class="info-label">Para recibir mejor atención</p>
                        <p class="text-sm text-soft leading-6">Describe qué sucede, cuándo empezó y a quién afecta para facilitar el seguimiento.</p>
                    </div>
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>
