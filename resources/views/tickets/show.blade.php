<x-app-layout>
    @php
        $currentUser = auth()->user();
        $isAdmin = $currentUser->isAdmin();
        $isAgent = $currentUser->isAgent();
        $canManage = $isAdmin || $isAgent;
        $isRequester = $currentUser->isUserRole() && $ticket->created_by === $currentUser->id;
        $statusSlug = strtolower((string) ($ticket->status?->slug ?? ''));
        $requesterCanAddFollowUp = $isRequester && $ticket->canRequesterAddFollowUp();
        $requesterCanRequestReview = $isRequester && $ticket->canRequesterRequestReview();
        $requesterCanClose = $isRequester && $ticket->canRequesterCloseWithSatisfaction();
        $requesterFinalized = $isRequester && $ticket->isFinalized();
        $operatorCanAddFollowUp = $canManage && (!$ticket->isFinalized() || $isAdmin);

        $priorityName = $ticket->priorityLabel();
        $priorityLower = strtolower($priorityName);
        $priorityClass = 'badge-slate';
        if (str_contains($priorityLower, 'crítica') || str_contains($priorityLower, 'critica') || str_contains($priorityLower, 'urgente') || str_contains($priorityLower, 'alta')) $priorityClass = 'badge-rose';
        elseif (str_contains($priorityLower, 'media')) $priorityClass = 'badge-amber';
        elseif (str_contains($priorityLower, 'baja')) $priorityClass = 'badge-emerald';

        $attentionClass = 'badge-slate';
        $creatorPosition = $ticket->creator?->position_level ?? 'operativo';
        if ($creatorPosition === 'director_general') $attentionClass = 'badge-rose';
        elseif ($creatorPosition === 'subdirector') $attentionClass = 'badge-amber';
        elseif ($creatorPosition === 'gerente') $attentionClass = 'badge-blue';

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

        $statusClassForOperator = function ($status): string {
            $slug = strtolower((string) ($status?->slug ?? ''));
            if (in_array($slug, ['cerrado', 'resuelto'], true)) return 'badge-emerald';
            if ($slug === 'en-espera-usuario' || $slug === 'en-espera-proveedor') return 'badge-amber';
            if ($slug === 'cancelado') return 'badge-slate';
            return 'badge-blue';
        };

        $formatRemainingTime = function ($dueAt): string {
            return \App\Support\MexicoCityTime::remaining($dueAt);
        };

        $finishedAt = $ticket->closed_at ?? $ticket->resolved_at;

        $userResponseMain = $ticket->first_response_due_at ? $formatRemainingTime($ticket->first_response_due_at) : 'Pendiente';
        $userResponseClass = $ticket->first_response_due_at ? 'badge-blue' : 'badge-slate';
        $userResponseHelp = $ticket->first_response_due_at
            ? 'Estimado antes del ' . \App\Support\MexicoCityTime::dateTime($ticket->first_response_due_at) . '.'
            : 'Aparecerá cuando se registre el seguimiento inicial.';

        if ($ticket->first_responded_at) {
            $userResponseMain = 'Con actualización';
            $userResponseHelp = 'Actualización registrada el ' . \App\Support\MexicoCityTime::dateTime($ticket->first_responded_at) . '.';
            $userResponseClass = 'badge-emerald';
        } elseif ($userResponseMain === 'Vencido') {
            $userResponseMain = 'En seguimiento';
            $userResponseClass = 'badge-blue';
        }

        $userResolutionMain = $ticket->resolution_due_at ? $formatRemainingTime($ticket->resolution_due_at) : 'Pendiente';
        $userResolutionClass = $ticket->resolution_due_at ? 'badge-blue' : 'badge-slate';
        $userResolutionHelp = $ticket->resolution_due_at
            ? 'Estimado antes del ' . \App\Support\MexicoCityTime::dateTime($ticket->resolution_due_at) . '.'
            : 'Se mostrará cuando haya una estimación disponible.';

        if ($finishedAt) {
            $userResolutionMain = $ticket->closed_at ? 'Finalizada' : 'Solución registrada';
            $userResolutionHelp = $ticket->closed_at
                ? 'Solicitud finalizada el ' . \App\Support\MexicoCityTime::dateTime($finishedAt) . '.'
                : 'Actualización registrada el ' . \App\Support\MexicoCityTime::dateTime($finishedAt) . '.';
            $userResolutionClass = 'badge-emerald';
        } elseif ($userResolutionMain === 'Vencido') {
            $userResolutionMain = 'En seguimiento';
            $userResolutionClass = 'badge-blue';
        }

        $responseLabel = $ticket->first_response_due_at ? $formatRemainingTime($ticket->first_response_due_at) : 'Sin tiempo';
        $responseClass = $responseLabel === 'Vencido' ? 'badge-rose' : ($ticket->first_response_due_at ? 'badge-blue' : 'badge-slate');
        if ($ticket->first_responded_at) {
            $responseLabel = 'Respondido';
            $responseClass = 'badge-emerald';
        }

        $resolutionLabel = $ticket->resolution_due_at ? $formatRemainingTime($ticket->resolution_due_at) : 'Sin tiempo';
        $resolutionClass = $resolutionLabel === 'Vencido' ? 'badge-rose' : ($ticket->resolution_due_at ? 'badge-blue' : 'badge-slate');
        if ($finishedAt) {
            $resolutionLabel = $ticket->closed_at ? 'Finalizada' : 'Solución registrada';
            $resolutionClass = 'badge-emerald';
        }
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-[var(--primary)]">{{ $canManage ? 'Ticket' : 'Solicitud' }} {{ $ticket->folio }}</p>
                <h1 class="page-title">{{ $ticket->subject }}</h1>
                <p class="page-subtitle">{{ $canManage ? 'Revisión y atención del caso.' : 'Consulta el avance y seguimiento de tu solicitud.' }}</p>
            </div>
            <a href="{{ route('tickets.index') }}" class="app-btn-secondary">{{ $canManage ? 'Volver a bandeja' : 'Volver a mis solicitudes' }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap space-y-6">
            @if(session('success'))<div class="flash-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="flash-error">{{ session('error') }}</div>@endif

            @if ($errors->any())
                <div class="flash-error" role="alert" aria-live="polite">
                    <p class="font-semibold mb-2">Revisa la información:</p>
                    <ul class="list-disc pl-5 text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($requesterFinalized)
                <div class="app-notice">
                    <p class="font-semibold text-[var(--text-main)]">Solicitud finalizada</p>
                    <p class="text-soft text-sm mt-1">Esta solicitud quedó cerrada. Puedes consultar su seguimiento, calificación y archivos, pero ya no es posible agregar mensajes, adjuntar evidencia ni solicitar revisión adicional. Si necesitas apoyo de nuevo, registra una nueva solicitud.</p>
                </div>
            @elseif($requesterCanRequestReview)
                <div class="app-notice">
                    <p class="font-semibold text-[var(--text-main)]">Solución registrada</p>
                    <p class="text-soft text-sm mt-1">Revisa la atención recibida. Si tu problema quedó resuelto, finaliza la solicitud y califica el servicio. Si el problema continúa, solicita una revisión adicional antes de finalizarla. Si no hay respuesta en 24 horas, la solicitud se finalizará automáticamente para mantener actualizado el seguimiento.</p>
                </div>
            @endif

            @if($isRequester)
                <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    <div class="metric-card"><p class="metric-label">Estado</p><p class="mt-2"><span class="app-badge {{ $statusBadgeForUser($ticket->status) }}">{{ $statusForUser($ticket->status) }}</span></p></div>
                    <div class="metric-card"><p class="metric-label">Registro</p><p class="mt-2 font-semibold text-[var(--text-main)]">{{ \App\Support\MexicoCityTime::dateTime($ticket->opened_at) }}</p></div>
                    <div class="metric-card"><p class="metric-label">Tiempo de respuesta</p><p class="mt-2"><span class="app-badge {{ $userResponseClass }}">{{ $userResponseMain }}</span></p><p class="text-xs text-soft mt-2">{{ $userResponseHelp }}</p></div>
                    <div class="metric-card"><p class="metric-label">Solución estimada</p><p class="mt-2"><span class="app-badge {{ $userResolutionClass }}">{{ $userResolutionMain }}</span></p><p class="text-xs text-soft mt-2">{{ $userResolutionHelp }}</p></div>
                </section>
            @else
                <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    <div class="metric-card"><p class="metric-label">Estado</p><p class="mt-2"><span class="app-badge {{ $statusClassForOperator($ticket->status) }}">{{ $statusForOperator($ticket->status) }}</span></p></div>
                    <div class="metric-card"><p class="metric-label">Prioridad</p><p class="mt-2"><span class="app-badge {{ $priorityClass }}">{{ $priorityName }}</span></p></div>
                    <div class="metric-card"><p class="metric-label">Respuesta</p><p class="mt-2"><span class="app-badge {{ $responseClass }}">{{ $responseLabel }}</span></p></div>
                    <div class="metric-card"><p class="metric-label">Solución</p><p class="mt-2"><span class="app-badge {{ $resolutionClass }}">{{ $resolutionLabel }}</span></p></div>
                </section>
            @endif

            <section class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
                <div class="xl:col-span-2 space-y-6">
                    <div class="app-card p-6">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h2 class="section-title">{{ $isRequester ? 'Detalle de la solicitud' : 'Resumen del caso' }}</h2>
                                <p class="text-soft text-sm">{{ $isRequester ? 'Información registrada para seguimiento.' : 'Información reportada por el solicitante.' }}</p>
                            </div>
                            @if($canManage)
                                <span class="app-badge {{ $attentionClass }}">{{ $ticket->creator?->attentionLabel() ?? 'Operativo' }}</span>
                            @endif
                        </div>

                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="info-panel md:col-span-2"><p class="info-label">Asunto</p><p class="info-value">{{ $ticket->subject }}</p></div>
                            <div class="info-panel md:col-span-2"><p class="info-label">Descripción</p><p class="text-[var(--text-main)] leading-7 whitespace-pre-line">{{ $ticket->description }}</p></div>
                            <div class="info-panel"><p class="info-label">Área relacionada</p><p class="info-value">{{ $ticket->category->name ?? 'Sin área' }}</p></div>
                            <div class="info-panel"><p class="info-label">Tipo de apoyo</p><p class="info-value">{{ $ticket->requestTypeLabel() }}</p></div>
                            <div class="info-panel"><p class="info-label">Afectación reportada</p><p class="info-value">{{ $ticket->reportedImpactLabel() }}</p></div>
                            @if($canManage)
                                <div class="info-panel"><p class="info-label">Solicitante</p><p class="info-value">{{ $ticket->creator->name ?? 'Sin solicitante' }}</p><p class="info-subvalue">{{ $ticket->creator->email ?? '' }}</p></div>
                            @endif
                        </div>
                    </div>

                    <div class="app-card p-6">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h2 class="section-title">Archivos de soporte</h2>
                                <p class="text-soft text-sm">{{ $canManage ? 'Evidencia original enviada al registrar la solicitud.' : 'Evidencia o documentos que adjuntaste al registrar la solicitud.' }}</p>
                            </div>
                            <span class="app-badge badge-slate">{{ $publicAttachments->count() }} archivo{{ $publicAttachments->count() === 1 ? '' : 's' }}</span>
                        </div>

                        @if($isRequester)
                            <div class="locked-panel mt-5">
                                <p class="font-semibold text-[var(--text-main)]">Evidencia protegida</p>
                                <p class="text-soft text-sm mt-1">Los archivos de soporte solo se adjuntan al registrar la solicitud. Después de enviarla no es posible agregar ni eliminar evidencia para conservar íntegro el seguimiento del caso.</p>
                            </div>
                        @elseif($canManage)
                            <div class="locked-panel mt-5">
                                <p class="font-semibold text-[var(--text-main)]">Evidencia original del solicitante</p>
                                <p class="text-soft text-sm mt-1">Esta sección conserva los archivos recibidos al crear la solicitud. La documentación operativa que agregue mesa de ayuda se registra como archivo interno.</p>
                            </div>
                        @endif

                        <div class="attachment-grid mt-6">
                            @forelse($publicAttachments as $attachment)
                                @include('tickets.partials.attachment-card', [
                                    'attachment' => $attachment,
                                    'canDelete' => false,
                                    'isInternalSection' => false,
                                ])
                            @empty
                                <div class="empty-state py-8 md:col-span-2">
                                    <p class="font-semibold text-[var(--text-main)]">Sin archivos adjuntos.</p>
                                    <p class="text-soft text-sm mt-1">Cuando se agreguen evidencias aparecerán aquí con vista previa cuando el formato lo permita.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @if($requesterCanClose)
                        <div class="app-card p-6 solution-review-card">
                            <div class="solution-review-header">
                                <div class="solution-review-icon">✓</div>
                                <div>
                                    <p class="eyebrow">Solución registrada</p>
                                    <h2 class="section-title">¿La solución resolvió tu problema?</h2>
                                    <p class="text-soft text-sm mt-1">Elige una opción para continuar con el seguimiento. Si no respondes en 24 horas, la solicitud se finalizará automáticamente.</p>
                                </div>
                            </div>

                            <div class="solution-choice-grid mt-6">
                                <div class="solution-choice-card solution-choice-ok">
                                    <div class="solution-choice-head">
                                        <div class="solution-choice-dot">1</div>
                                        <div>
                                            <h3>Sí, quedó resuelto</h3>
                                            <p>Finaliza la solicitud y califica la atención recibida.</p>
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('tickets.accept-solution', $ticket) }}" class="space-y-5 mt-5">
                                        @csrf
                                        <div>
                                            <p class="form-label">¿Cómo calificarías la atención?</p>
                                            <div class="satisfaction-options" role="radiogroup" aria-label="Calificación de la atención">
                                                <label class="satisfaction-option">
                                                    <input type="radio" name="satisfaction_rating" value="5" required>
                                                    <span><strong>Excelente</strong><small>5 / 5</small></span>
                                                </label>
                                                <label class="satisfaction-option">
                                                    <input type="radio" name="satisfaction_rating" value="4" required>
                                                    <span><strong>Buena</strong><small>4 / 5</small></span>
                                                </label>
                                                <label class="satisfaction-option">
                                                    <input type="radio" name="satisfaction_rating" value="3" required>
                                                    <span><strong>Regular</strong><small>3 / 5</small></span>
                                                </label>
                                                <label class="satisfaction-option">
                                                    <input type="radio" name="satisfaction_rating" value="2" required>
                                                    <span><strong>Mala</strong><small>2 / 5</small></span>
                                                </label>
                                                <label class="satisfaction-option">
                                                    <input type="radio" name="satisfaction_rating" value="1" required>
                                                    <span><strong>Muy mala</strong><small>1 / 5</small></span>
                                                </label>
                                            </div>
                                        </div>

                                        <div>
                                            <label for="satisfaction_comment" class="form-label">Comentario opcional</label>
                                            <textarea id="satisfaction_comment" name="satisfaction_comment" rows="3" placeholder="Puedes contarnos qué salió bien o qué podríamos mejorar."></textarea>
                                        </div>

                                        <div class="closure-warning">
                                            <p><strong>Importante:</strong> al finalizar, esta solicitud pasará a tu historial y ya no podrás agregar mensajes, archivos ni pedir revisión adicional desde este seguimiento.</p>
                                        </div>

                                        <button type="submit" class="app-btn-primary w-full md:w-auto">Finalizar y calificar</button>
                                    </form>
                                </div>

                                @if($requesterCanRequestReview)
                                    <div class="solution-choice-card solution-choice-review">
                                        <div class="solution-choice-head">
                                            <div class="solution-choice-dot">2</div>
                                            <div>
                                                <h3>No, necesito revisión adicional</h3>
                                                <p>Usa esta opción solo si el problema continúa o falta algo por atender.</p>
                                            </div>
                                        </div>

                                        <div class="review-info-box mt-5">
                                            <p class="font-semibold">Esta opción mantiene abierta tu solicitud.</p>
                                            <p class="mt-1">El equipo de soporte revisará nuevamente el caso con la información que compartas.</p>
                                        </div>

                                        <form method="POST" action="{{ route('tickets.reopen', $ticket) }}" class="mt-5 space-y-4">
                                            @csrf
                                            <div>
                                                <label for="reopen_reason" class="form-label">¿Qué sigue fallando?</label>
                                                <textarea id="reopen_reason" name="reopen_reason" rows="5" required placeholder="Describe de forma breve qué no se resolvió, qué error continúa o qué falta por atender."></textarea>
                                            </div>
                                            <button type="submit" class="app-btn-warning w-full md:w-auto">Solicitar revisión adicional</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($canManage)
                        <div class="app-card p-6">
                            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between mb-5">
                                <div>
                                    <h2 class="section-title">Atención y seguimiento</h2>
                                    <p class="text-soft text-sm">Separa la comunicación visible para el solicitante de las notas y evidencias internas de mesa de ayuda.</p>
                                </div>
                                <span class="app-badge badge-blue">Mesa de ayuda</span>
                            </div>

                            <div class="operator-followup-grid">
                                <div class="operator-followup-column operator-followup-public">
                                    <div class="operator-followup-header">
                                        <div>
                                            <p class="eyebrow">Visible para el solicitante</p>
                                            <h3>Respuesta al solicitante</h3>
                                        </div>
                                    </div>
                                    <p class="text-soft text-sm mb-4">Usa este espacio para informar avances, pedir información o comunicar la solución.</p>

                                    @if($operatorCanAddFollowUp)
                                        <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" class="space-y-4 mb-6">
                                            @csrf
                                            <div>
                                                <label for="comment" class="form-label">Mensaje para el solicitante</label>
                                                <textarea id="comment" name="comment" rows="5" required placeholder="Escribe una respuesta clara y breve para el solicitante."></textarea>
                                            </div>
                                            <button type="submit" class="app-btn-primary w-full">Enviar respuesta</button>
                                        </form>
                                    @else
                                        <div class="locked-panel mb-6">
                                            <p class="font-semibold text-[var(--text-main)]">Caso finalizado</p>
                                            <p class="text-soft text-sm mt-1">Este caso ya no admite respuestas desde la bandeja del agente. Puedes consultarlo desde el historial o reabrirlo si requiere seguimiento adicional.</p>
                                        </div>
                                    @endif

                                    <div class="operator-thread">
                                        @forelse($publicComments as $comment)
                                            <div class="info-panel">
                                                <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                                                    <p class="font-semibold text-[var(--text-main)]">{{ $comment->user->name ?? 'Usuario' }}</p>
                                                    <p class="text-xs text-soft">{{ \App\Support\MexicoCityTime::dateTime($comment->created_at) }}</p>
                                                </div>
                                                <p class="mt-3 text-[var(--text-main)] leading-7 whitespace-pre-line">{{ $comment->comment }}</p>
                                            </div>
                                        @empty
                                            <p class="text-soft text-sm">Aún no hay respuestas registradas para el solicitante.</p>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="operator-followup-column operator-followup-internal">
                                    <div class="operator-followup-header">
                                        <div>
                                            <p class="eyebrow">Solo mesa de ayuda</p>
                                            <h3>Notas internas</h3>
                                        </div>
                                    </div>
                                    <p class="text-soft text-sm mb-4">Registra hallazgos, acciones técnicas y evidencia operativa. Esta información no será visible para el solicitante.</p>

                                    @if($operatorCanAddFollowUp)
                                        <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" class="space-y-4 mb-6">
                                            @csrf
                                            <input type="hidden" name="is_internal" value="1">
                                            <div>
                                                <label for="internal_comment" class="form-label">Nota interna</label>
                                                <textarea id="internal_comment" name="comment" rows="5" required placeholder="Agrega información útil para el equipo de soporte."></textarea>
                                            </div>
                                            <button type="submit" class="app-btn-secondary w-full">Guardar nota interna</button>
                                        </form>
                                    @else
                                        <div class="locked-panel mb-6">
                                            <p class="font-semibold text-[var(--text-main)]">Caso finalizado</p>
                                            <p class="text-soft text-sm mt-1">Este caso ya no admite notas operativas desde la bandeja del agente.</p>
                                        </div>
                                    @endif

                                    <div class="operator-thread">
                                        @forelse($internalComments as $comment)
                                            <div class="info-panel bg-slate-50">
                                                <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                                                    <p class="font-semibold text-[var(--text-main)]">{{ $comment->user->name ?? 'Usuario' }}</p>
                                                    <p class="text-xs text-soft">{{ \App\Support\MexicoCityTime::dateTime($comment->created_at) }}</p>
                                                </div>
                                                <p class="mt-3 text-[var(--text-main)] leading-7 whitespace-pre-line">{{ $comment->comment }}</p>
                                            </div>
                                        @empty
                                            <p class="text-soft text-sm">Aún no hay notas internas.</p>
                                        @endforelse
                                    </div>

                                    <div class="mt-6 pt-6 border-t border-slate-200">
                                        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between mb-4">
                                            <h4 class="font-semibold text-[var(--text-main)]">Archivos internos</h4>
                                            <span class="app-badge badge-slate">{{ $internalAttachments->count() }} archivo{{ $internalAttachments->count() === 1 ? '' : 's' }}</span>
                                        </div>
                                        @if($operatorCanAddFollowUp)
                                            <form method="POST" action="{{ route('tickets.attachments.store', $ticket) }}" enctype="multipart/form-data" class="attachment-upload-panel attachment-upload-panel-operator mb-5">
                                                @csrf
                                                <input type="hidden" name="is_internal" value="1">
                                                <div class="attachment-upload-text">
                                                    <p class="form-label mb-1">Agregar archivo interno</p>
                                                    <p class="text-xs text-soft">Sube evidencia, capturas o documentos de trabajo. Estos archivos solo serán visibles para mesa de ayuda.</p>
                                                </div>
                                                <div class="attachment-upload-control">
                                                    <label for="internal_attachments" class="file-picker-button">Seleccionar archivos</label>
                                                    <input id="internal_attachments" type="file" name="attachments[]" multiple class="file-picker-input">
                                                    <p class="text-xs text-soft mt-2">Máximo 5 archivos, 10 MB por archivo.</p>
                                                </div>
                                                <button type="submit" class="app-btn-secondary w-full">Guardar archivos internos</button>
                                            </form>
                                        @endif

                                        <div class="attachment-grid">
                                            @forelse($internalAttachments as $attachment)
                                                @include('tickets.partials.attachment-card', [
                                                    'attachment' => $attachment,
                                                    'canDelete' => $isAdmin || $attachment->uploaded_by === auth()->id(),
                                                    'isInternalSection' => true,
                                                ])
                                            @empty
                                                <p class="text-soft text-sm">Aún no hay archivos internos.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="app-card p-6">
                            <h2 class="section-title">Seguimiento</h2>
                            <p class="text-soft text-sm mb-4">Aquí verás las actualizaciones de tu solicitud.</p>
                            @if($requesterCanAddFollowUp)
                                <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" class="space-y-4 mb-6">
                                    @csrf
                                    <div>
                                        <label for="comment" class="form-label">Mensaje</label>
                                        <textarea id="comment" name="comment" rows="4" required></textarea>
                                    </div>
                                    <button type="submit" class="app-btn-primary">Enviar mensaje</button>
                                </form>
                            @elseif($isRequester)
                                <div class="locked-panel mb-6">
                                    <p class="font-semibold text-[var(--text-main)]">Seguimiento cerrado para mensajes</p>
                                    <p class="text-soft text-sm mt-1">Esta solicitud ya no admite mensajes adicionales. Si tiene una solución registrada y no resolvió tu problema, solicita revisión adicional antes de finalizarla.</p>
                                </div>
                            @endif

                            <div class="space-y-4">
                                @forelse($publicComments as $comment)
                                    <div class="info-panel">
                                        <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                                            <p class="font-semibold text-[var(--text-main)]">{{ $comment->user->name ?? 'Usuario' }}</p>
                                            <p class="text-xs text-soft">{{ \App\Support\MexicoCityTime::dateTime($comment->created_at) }}</p>
                                        </div>
                                        <p class="mt-3 text-[var(--text-main)] leading-7 whitespace-pre-line">{{ $comment->comment }}</p>
                                    </div>
                                @empty
                                    <p class="text-soft text-sm">Aún no hay actualizaciones.</p>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>

                <aside class="space-y-6">
                    @if($isAdmin)
                        <div class="app-card p-6">
                            <h2 class="section-title">Control del ticket</h2>
                            <p class="text-soft text-sm mb-5">Ajusta responsable, prioridad y estado cuando sea necesario.</p>
                            <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="space-y-4">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label for="priority_id" class="form-label">Prioridad</label>
                                    <select id="priority_id" name="priority_id">
                                        <option value="">Sin clasificar</option>
                                        @foreach($priorities as $priority)
                                            <option value="{{ $priority->id }}" {{ old('priority_id', $ticket->priority_id) == $priority->id ? 'selected' : '' }}>{{ $priority->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="impact" class="form-label">Impacto</label>
                                    <select id="impact" name="impact">
                                        <option value="">Sin clasificar</option>
                                        @foreach($impactOptions as $key => $label)
                                            <option value="{{ $key }}" {{ old('impact', $ticket->impact) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="urgency" class="form-label">Urgencia</label>
                                    <select id="urgency" name="urgency">
                                        <option value="">Sin clasificar</option>
                                        @foreach($urgencyOptions as $key => $label)
                                            <option value="{{ $key }}" {{ old('urgency', $ticket->urgency) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="status_id" class="form-label">Estado</label>
                                    <select id="status_id" name="status_id">
                                        @foreach($statuses as $status)
                                            <option value="{{ $status->id }}" {{ old('status_id', $ticket->status_id) == $status->id ? 'selected' : '' }}>{{ $statusForOperator($status) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="assigned_to" class="form-label">Responsable</label>
                                    <select id="assigned_to" name="assigned_to">
                                        <option value="">Sin asignar</option>
                                        @foreach($agents as $agent)
                                            <option value="{{ $agent->id }}" {{ old('assigned_to', $ticket->assigned_to) == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="app-btn-primary w-full">Guardar cambios</button>
                            </form>
                        </div>
                    @elseif($isAgent)
                        <div class="app-card p-6">
                            <h2 class="section-title">Atención del caso</h2>
                            <p class="text-soft text-sm mb-5">Actualiza el avance. La reasignación queda reservada al administrador.</p>
                            <div class="space-y-4 mb-5">
                                <div class="info-panel"><p class="info-label">Criterio de atención</p><p class="info-value"><span class="app-badge {{ $priorityClass }}">{{ $priorityName }}</span></p></div>
                                <div class="info-panel"><p class="info-label">Impacto / urgencia</p><p class="info-value">{{ $ticket->impactLabel() }} / {{ $ticket->urgencyLabel() }}</p></div>
                                <div class="info-panel"><p class="info-label">Responsable</p><p class="info-value">{{ $ticket->assignee->name ?? 'Disponible para tomar' }}</p></div>
                            </div>

                            @if($canSelfAssign)
                                <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="mb-5">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="take_ticket" value="1">
                                    <button type="submit" class="app-btn-secondary w-full">Tomar este caso</button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="space-y-4">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label for="status_id" class="form-label">Estado</label>
                                    <select id="status_id" name="status_id">
                                        @foreach($statuses as $status)
                                            <option value="{{ $status->id }}" {{ old('status_id', $ticket->status_id) == $status->id ? 'selected' : '' }}>{{ $statusForOperator($status) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="app-btn-primary w-full">Actualizar avance</button>
                            </form>
                        </div>
                    @endif

                    <div class="app-card p-6">
                        <h2 class="section-title">{{ $canManage ? 'Datos operativos' : 'Estado de la solicitud' }}</h2>
                        <div class="space-y-4">
                            @if($canManage)
                                <div class="info-panel"><p class="info-label">Solicitante</p><p class="info-value">{{ $ticket->creator->name ?? 'Sin solicitante' }}</p><p class="info-subvalue">{{ $ticket->creator?->attentionLabel() ?? 'Operativo' }}</p></div>
                                <div class="info-panel"><p class="info-label">Responsable</p><p class="info-value">{{ $ticket->assignee->name ?? 'Sin asignar' }}</p></div>
                                <div class="info-panel"><p class="info-label">Fechas compromiso</p><p class="info-value">Apertura: {{ \App\Support\MexicoCityTime::dateTime($ticket->opened_at) }}</p><p class="info-subvalue">Respuesta: {{ \App\Support\MexicoCityTime::dateTime($ticket->first_response_due_at, 'Sin tiempo') }}</p><p class="info-subvalue">Solución: {{ \App\Support\MexicoCityTime::dateTime($ticket->resolution_due_at, 'Sin tiempo') }}</p></div>
                                @if($ticket->satisfaction_submitted_at)
                                    <div class="info-panel"><p class="info-label">Satisfacción</p><p class="info-value">{{ $ticket->satisfaction_rating ?? 'Sin calificación' }} / 5</p><p class="info-subvalue">{{ $ticket->satisfaction_comment }}</p></div>
                                @endif
                            @else
                                <div class="info-panel"><p class="info-label">Última actualización</p><p class="info-value">{{ \App\Support\MexicoCityTime::dateTime($ticket->updated_at) }}</p></div>
                                <div class="info-panel"><p class="info-label">Tiempo de respuesta</p><p class="info-value"><span class="app-badge {{ $userResponseClass }}">{{ $userResponseMain }}</span></p><p class="info-subvalue mt-2">{{ $userResponseHelp }}</p></div>
                                <div class="info-panel"><p class="info-label">Solución estimada</p><p class="info-value"><span class="app-badge {{ $userResolutionClass }}">{{ $userResolutionMain }}</span></p><p class="info-subvalue mt-2">{{ $userResolutionHelp }}</p></div>
                                @if($ticket->satisfaction_submitted_at)
                                    <div class="info-panel"><p class="info-label">Tu calificación</p><p class="info-value">{{ $ticket->satisfaction_rating ?? 'Sin calificación' }} / 5</p><p class="info-subvalue">{{ $ticket->satisfaction_comment }}</p></div>
                                @endif
                            @endif
                        </div>
                    </div>

                    <div class="app-card p-6">
                        <h2 class="section-title">{{ $canManage ? 'Historial' : 'Actividad reciente' }}</h2>
                        <div class="space-y-4">
                            @forelse($ticket->histories->sortByDesc('changed_at') as $history)
                                <div class="border-l-4 border-violet-200 pl-4">
                                    @if($canManage)
                                        <p class="font-semibold text-[var(--text-main)]">{{ $history->notes ?? 'Actualización de ticket' }}</p>
                                        <p class="text-xs text-soft mt-1">{{ $history->changedBy->name ?? 'Sistema' }} · {{ \App\Support\MexicoCityTime::dateTime($history->changed_at) }}</p>
                                        <p class="text-xs text-soft mt-1">{{ $history->previousStatus ? $statusForOperator($history->previousStatus) : 'Sin estado previo' }} → {{ $statusForOperator($history->newStatus) }}</p>
                                    @else
                                        <p class="font-semibold text-[var(--text-main)]">Actualización de seguimiento</p>
                                        <p class="text-xs text-soft mt-1">{{ \App\Support\MexicoCityTime::dateTime($history->changed_at) }}</p>
                                        <p class="text-xs text-soft mt-1">Estado: {{ $statusForUser($history->newStatus) }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-soft text-sm">Aún no hay actividad registrada.</p>
                            @endforelse
                        </div>
                    </div>

                    @if($isAdmin)
                        <div class="app-card p-6">
                            <h2 class="section-title">Bitácora de auditoría</h2>
                            <p class="text-soft text-sm mb-4">Acciones relevantes registradas en el caso.</p>
                            <div class="space-y-3 max-h-96 overflow-y-auto">
                                @forelse($auditLogs as $log)
                                    <div class="border-l-4 border-slate-200 pl-4">
                                        <p class="font-semibold text-[var(--text-main)]">{{ $log->description }}</p>
                                        <p class="text-xs text-soft mt-1">{{ $log->user->name ?? 'Sistema' }} · {{ \App\Support\MexicoCityTime::dateTime($log->created_at) }}</p>
                                        <p class="text-xs text-soft mt-1">{{ $log->action }}</p>
                                    </div>
                                @empty
                                    <p class="text-soft text-sm">Aún no hay registros de auditoría.</p>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    @if($canManage && ($ticket->isResolved() || $ticket->isClosed()))
                        <div class="app-card p-6">
                            <h2 class="section-title">Reapertura</h2>
                            <p class="text-soft text-sm mb-4">Reabre el ticket si requiere seguimiento adicional.</p>
                            <form method="POST" action="{{ route('tickets.reopen', $ticket) }}" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="admin_reopen_reason" class="form-label">Motivo</label>
                                    <textarea id="admin_reopen_reason" name="reopen_reason" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="app-btn-warning w-full">Reabrir ticket</button>
                            </form>
                        </div>
                    @endif
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>
