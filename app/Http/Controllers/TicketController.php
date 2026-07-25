<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketStatus;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Services\TicketClassificationService;
use App\Services\AuditLogger;
use App\Services\InternalNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    private const MAX_ACTIVE_TICKETS_PER_AGENT = 5;

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $terminalStatusSlugs = ['resuelto', 'cerrado', 'cancelado'];
        $requesterClosedStatusSlugs = ['cerrado', 'cancelado'];
        $viewMode = (string) $request->input('view', 'active');

        if (!in_array($viewMode, ['active', 'history', 'all'], true)) {
            $viewMode = 'active';
        }

        $query = Ticket::query()
            ->with([
                'category',
                'priority',
                'status',
                'creator.role',
                'assignee.role',
            ])
            ->leftJoin('priorities', 'tickets.priority_id', '=', 'priorities.id')
            ->leftJoin('ticket_statuses as statuses', 'tickets.status_id', '=', 'statuses.id')
            ->leftJoin('users as creators', 'tickets.created_by', '=', 'creators.id')
            ->leftJoin('users as assignees', 'tickets.assigned_to', '=', 'assignees.id')
            ->select('tickets.*');

        if ($user->isAdmin()) {
            // El administrador puede ver todos los tickets.
        } elseif ($user->isAgent()) {
            if ($viewMode === 'history') {
                // El historial del agente muestra únicamente casos que fueron suyos.
                $query->where('tickets.assigned_to', $user->id);
            } else {
                // La bandeja activa muestra sus casos actuales y casos disponibles para tomar.
                $query->where(function ($q) use ($user) {
                    $q->where('tickets.assigned_to', $user->id)
                        ->orWhereNull('tickets.assigned_to');
                });
            }
        } elseif ($user->isUserRole()) {
            $query->where('tickets.created_by', $user->id);
        } else {
            abort(403, 'Tu usuario no tiene un rol válido asignado.');
        }

        if ($user->isAdmin() || $user->isAgent()) {
            if ($viewMode === 'history') {
                $query->whereIn('statuses.slug', $terminalStatusSlugs);
            } elseif ($viewMode === 'active') {
                $query->whereNotIn('statuses.slug', $terminalStatusSlugs);
            }
        } elseif ($user->isUserRole()) {
            if ($viewMode === 'history') {
                $query->whereIn('statuses.slug', $requesterClosedStatusSlugs);
            } elseif ($viewMode === 'active') {
                $query->whereNotIn('statuses.slug', $requesterClosedStatusSlugs);
            }
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->q);

            $query->where(function ($q) use ($search) {
                $q->where('tickets.folio', 'like', "%{$search}%")
                    ->orWhere('tickets.subject', 'like', "%{$search}%")
                    ->orWhere('tickets.description', 'like', "%{$search}%")
                    ->orWhere('creators.name', 'like', "%{$search}%")
                    ->orWhere('creators.email', 'like', "%{$search}%")
                    ->orWhere('assignees.name', 'like', "%{$search}%");
            });
        }

        if ($user->isUserRole() && $request->filled('status_group')) {
            switch ($request->status_group) {
                case 'seguimiento':
                    $query->whereNotIn('statuses.slug', ['resuelto', 'cerrado', 'cancelado', 'en-espera-usuario']);
                    break;
                case 'requiere_respuesta':
                    $query->where('statuses.slug', 'en-espera-usuario');
                    break;
                case 'solucion_registrada':
                    $query->where('statuses.slug', 'resuelto');
                    break;
                case 'finalizadas':
                    $query->where('statuses.slug', 'cerrado');
                    break;
                case 'canceladas':
                    $query->where('statuses.slug', 'cancelado');
                    break;
            }
        } elseif ($request->filled('status')) {
            $query->where('tickets.status_id', $request->status);
        }

        if (($user->isAdmin() || $user->isAgent()) && $request->filled('priority')) {
            if ($request->priority === 'sin_clasificar') {
                $query->whereNull('tickets.priority_id');
            } else {
                $query->where('tickets.priority_id', $request->priority);
            }
        }

        if ($request->filled('category')) {
            $query->where('tickets.category_id', $request->category);
        }

        if ($request->filled('request_type') && array_key_exists($request->request_type, Ticket::REQUEST_TYPES)) {
            $query->where('tickets.request_type', $request->request_type);
        }

        if (($user->isAdmin() || $user->isAgent()) && $request->filled('user_level') && array_key_exists($request->user_level, User::attentionLevels())) {
            $query->where('creators.position_level', $request->user_level);
        }

        if (($user->isAdmin() || $user->isAgent()) && $request->filled('assignment')) {
            switch ($request->assignment) {
                case 'sin_asignar':
                    $query->whereNull('tickets.assigned_to');
                    break;
                case 'asignados':
                    $query->whereNotNull('tickets.assigned_to');
                    break;
                case 'mis_tickets':
                    $query->where('tickets.assigned_to', $user->id);
                    break;
            }
        }

        if ($user->isAdmin() && $request->filled('agent_id')) {
            if ($request->agent_id === 'sin_asignar') {
                $query->whereNull('tickets.assigned_to');
            } else {
                $query->where('tickets.assigned_to', (int) $request->agent_id);
            }
        }

        $timeStatus = $request->input('time_status', $request->input('sla'));

        if (($user->isAdmin() || $user->isAgent()) && !empty($timeStatus)) {
            if ($timeStatus === 'vencido') {
                $query->where(function ($q) {
                    $q->where(function ($inner) {
                        $inner->whereNull('tickets.first_responded_at')
                            ->whereNotNull('tickets.first_response_due_at')
                            ->where('tickets.first_response_due_at', '<', now());
                    })->orWhere(function ($inner) {
                        $inner->whereNull('tickets.resolved_at')
                            ->whereNull('tickets.closed_at')
                            ->whereNotNull('tickets.resolution_due_at')
                            ->where('tickets.resolution_due_at', '<', now());
                    });
                });
            }

            if ($timeStatus === 'por_vencer') {
                $query->whereNull('tickets.resolved_at')
                    ->whereNull('tickets.closed_at')
                    ->whereNotNull('tickets.resolution_due_at')
                    ->whereBetween('tickets.resolution_due_at', [now(), now()->copy()->addHours(8)]);
            }

            if ($timeStatus === 'sin_clasificar') {
                $query->whereNull('tickets.priority_id');
            }
        }

        $summaryQuery = clone $query;

        $overdueCounter = function ($q) {
            $q->where(function ($due) {
                $due->where(function ($inner) {
                    $inner->whereNull('tickets.first_responded_at')
                        ->whereNotNull('tickets.first_response_due_at')
                        ->where('tickets.first_response_due_at', '<', now());
                })->orWhere(function ($inner) {
                    $inner->whereNull('tickets.resolved_at')
                        ->whereNull('tickets.closed_at')
                        ->whereNotNull('tickets.resolution_due_at')
                        ->where('tickets.resolution_due_at', '<', now());
                });
            });
        };

        $dueSoonCounter = function ($q) {
            $q->whereNull('tickets.resolved_at')
                ->whereNull('tickets.closed_at')
                ->whereNotNull('tickets.resolution_due_at')
                ->whereBetween('tickets.resolution_due_at', [now(), now()->copy()->addHours(8)]);
        };

        $summary = [
            'total' => (clone $summaryQuery)->count('tickets.id'),
            'active' => (clone $summaryQuery)->whereNotIn('statuses.slug', ['resuelto', 'cerrado', 'cancelado'])->count('tickets.id'),
            'unclassified' => (clone $summaryQuery)->whereNull('tickets.priority_id')->count('tickets.id'),
            'unassigned' => (clone $summaryQuery)->whereNull('tickets.assigned_to')->count('tickets.id'),
            'mine' => (clone $summaryQuery)->where('tickets.assigned_to', $user->id)->count('tickets.id'),
            'overdue' => (clone $summaryQuery)->where($overdueCounter)->count('tickets.id'),
            'due_soon' => (clone $summaryQuery)->where($dueSoonCounter)->count('tickets.id'),
            'user_followup' => (clone $summaryQuery)
                ->whereNotIn('statuses.slug', ['resuelto', 'cerrado', 'cancelado'])
                ->count('tickets.id'),
            'user_waiting' => (clone $summaryQuery)
                ->where('statuses.slug', 'en-espera-usuario')
                ->count('tickets.id'),
            'user_solution' => (clone $summaryQuery)
                ->where('statuses.slug', 'resuelto')
                ->count('tickets.id'),
            'user_finished' => (clone $summaryQuery)
                ->whereIn('statuses.slug', ['cerrado', 'cancelado'])
                ->count('tickets.id'),
            'history_solution' => (clone $summaryQuery)
                ->where('statuses.slug', 'resuelto')
                ->count('tickets.id'),
            'history_closed' => (clone $summaryQuery)
                ->where('statuses.slug', 'cerrado')
                ->count('tickets.id'),
            'history_cancelled' => (clone $summaryQuery)
                ->where('statuses.slug', 'cancelado')
                ->count('tickets.id'),
        ];

        $activeTabCount = null;
        $historyTabCount = null;

        if ($user->isAdmin() || $user->isAgent()) {
            $tabBaseQuery = function () use ($user) {
                $tabQuery = Ticket::query()
                    ->leftJoin('ticket_statuses as tab_statuses', 'tickets.status_id', '=', 'tab_statuses.id');

                if ($user->isAgent()) {
                    $tabQuery->where(function ($q) use ($user) {
                        $q->where('tickets.assigned_to', $user->id)
                            ->orWhereNull('tickets.assigned_to');
                    });
                }

                return $tabQuery;
            };

            $activeTabBase = $tabBaseQuery();
            $activeTabCount = (clone $activeTabBase)
                ->whereNotIn('tab_statuses.slug', $terminalStatusSlugs)
                ->count('tickets.id');

            $historyTabQuery = Ticket::query()
                ->leftJoin('ticket_statuses as tab_statuses', 'tickets.status_id', '=', 'tab_statuses.id')
                ->whereIn('tab_statuses.slug', $terminalStatusSlugs);

            if ($user->isAgent()) {
                $historyTabQuery->where('tickets.assigned_to', $user->id);
            }

            $historyTabCount = $historyTabQuery->count('tickets.id');
        } elseif ($user->isUserRole()) {
            $userTabBaseQuery = Ticket::query()
                ->leftJoin('ticket_statuses as tab_statuses', 'tickets.status_id', '=', 'tab_statuses.id')
                ->where('tickets.created_by', $user->id);

            $activeTabCount = (clone $userTabBaseQuery)
                ->whereNotIn('tab_statuses.slug', $requesterClosedStatusSlugs)
                ->count('tickets.id');

            $historyTabCount = (clone $userTabBaseQuery)
                ->whereIn('tab_statuses.slug', $requesterClosedStatusSlugs)
                ->count('tickets.id');
        }

        $order = $request->input('order', $user->isUserRole() ? 'recientes' : 'cola');

        switch ($order) {
            case 'cola':
                $query
                    ->orderByRaw('CASE WHEN tickets.assigned_to IS NULL THEN 0 ELSE 1 END ASC')
                    ->orderByRaw('CASE WHEN tickets.priority_id IS NULL THEN 0 ELSE 1 END ASC')
                    ->orderByRaw('COALESCE(creators.attention_weight, 20) DESC')
                    ->orderByDesc('tickets.created_at');
                break;
            case 'criticidad':
                $query
                    ->orderByRaw('CASE WHEN tickets.priority_id IS NULL THEN 1 ELSE 0 END ASC')
                    ->orderByRaw('COALESCE(priorities.level, 0) DESC')
                    ->orderByRaw('COALESCE(creators.attention_weight, 20) DESC')
                    ->orderByRaw('CASE WHEN tickets.resolution_due_at IS NULL THEN 1 ELSE 0 END ASC')
                    ->orderBy('tickets.resolution_due_at')
                    ->orderBy('tickets.opened_at');
                break;
            case 'antiguos':
                $query->orderBy('tickets.created_at');
                break;
            default:
                $query->orderByDesc('tickets.created_at');
                break;
        }

        $tickets = $query
            ->paginate(10)
            ->withQueryString();

        $statuses = $this->visibleWorkflowStatuses($user);
        $priorities = Priority::orderBy('level')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        $agents = User::whereHas('role', function ($q) {
            $q->where('name', 'Agente');
        })->orderBy('name')->get();
        $attentionLevels = User::attentionLevels();
        $requestTypes = Ticket::REQUEST_TYPES;

        return view('tickets.index', compact(
            'tickets',
            'statuses',
            'priorities',
            'categories',
            'agents',
            'attentionLevels',
            'requestTypes',
            'summary',
            'viewMode',
            'activeTabCount',
            'historyTabCount'
        ));
    }

    public function create(): View
    {
        return view('tickets.create', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'requestTypes' => Ticket::REQUEST_TYPES,
            'reportedImpactOptions' => Ticket::REPORTED_IMPACT_OPTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'category_id' => ['required', 'exists:categories,id'],
            'request_type' => ['required', Rule::in(array_keys(Ticket::REQUEST_TYPES))],
            'reported_impact' => ['nullable', Rule::in(array_keys(Ticket::REPORTED_IMPACT_OPTIONS))],

            'support_files' => ['nullable', 'array', 'max:5'],
            'support_files.*' => [
                'bail',
                'file',
                'max:10240',
                function ($attribute, $file, $fail) {
                    $allowedExtensions = [
                        'jpg',
                        'jpeg',
                        'png',
                        'pdf',
                        'doc',
                        'docx',
                        'xls',
                        'xlsx',
                        'txt',
                    ];

                    $extension = strtolower($file->getClientOriginalExtension());

                    if (! in_array($extension, $allowedExtensions, true)) {
                        $fail('El archivo "' . $file->getClientOriginalName() . '" no es un formato permitido. Usa: jpg, jpeg, png, pdf, doc, docx, xls, xlsx o txt.');
                    }
                },
            ],
        ], [
            'support_files.max' => 'Solo puedes adjuntar hasta 5 archivos de soporte.',
            'support_files.*.file' => 'Cada archivo de soporte debe ser un archivo válido.',
            'support_files.*.max' => 'Cada archivo de soporte no debe superar los 10 MB.',
        ], [
            'support_files' => 'archivos de soporte',
            'support_files.*' => 'archivo de soporte',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $newStatus = TicketStatus::where('slug', 'nuevo')->firstOrFail();
        $classificationService = app(TicketClassificationService::class);

        $ticket = DB::transaction(function () use ($validated, $user, $newStatus, $classificationService) {
            $selectedAgent = $this->getBestAvailableAgent();

            $ticket = new Ticket([
                'folio' => $this->generateFolio(),
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'request_type' => $validated['request_type'],
                'reported_impact' => $validated['reported_impact'] ?? null,
                'category_id' => (int) $validated['category_id'],
                'priority_id' => null,
                'status_id' => $newStatus->id,
                'created_by' => $user->id,
                'assigned_to' => $selectedAgent?->id,
                'opened_at' => now(),
            ]);

            $classificationService->applyStandardClassification($ticket, $user);
            $ticket->save();

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'previous_status_id' => null,
                'new_status_id' => $newStatus->id,
                'changed_by' => $user->id,
                'changed_at' => now(),
                'notes' => $selectedAgent
                    ? 'Ticket registrado, clasificado con criterios estándar y asignado automáticamente a ' . $selectedAgent->name . '.'
                    : 'Ticket registrado y clasificado con criterios estándar; quedó pendiente de asignación por falta de agentes disponibles dentro del límite de carga.',
            ]);

            return $ticket;
        });

        $uploadedSupportFiles = $this->storeInitialSupportFiles($request, $ticket, $user);

        app(AuditLogger::class)->log('ticket.created', 'Solicitud registrada.', $ticket, null, [
            'folio' => $ticket->folio,
            'assigned_to' => $ticket->assigned_to,
            'priority_id' => $ticket->priority_id,
            'support_files' => $uploadedSupportFiles,
        ]);

        if (!empty($uploadedSupportFiles)) {
            app(AuditLogger::class)->log('attachment.initial_uploaded', 'Se registraron archivos de soporte iniciales.', $ticket, null, [
                'files' => $uploadedSupportFiles,
            ]);
        }

        $notificationService = app(InternalNotificationService::class);

        if ($ticket->assigned_to) {
            $notificationService->createForUser(
                $ticket->assigned_to,
                $ticket,
                'Nuevo caso asignado',
                'Se te asignó una nueva solicitud para seguimiento.',
                'info'
            );
        } else {
            $notificationService->notifyAdmins(
                $ticket,
                'Solicitud sin responsable',
                'Hay una nueva solicitud pendiente de asignación.',
                'warning',
                route('tickets.index', ['assignment' => 'sin_asignar'])
            );
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Solicitud registrada correctamente. Podrás consultar aquí las actualizaciones.');
    }

    public function show(Ticket $ticket): View
    {
        $this->authorizeTicketAccess($ticket);

        /** @var User $user */
        $user = Auth::user();

        $ticket->load([
            'category',
            'priority',
            'status',
            'creator.role',
            'assignee.role',
            'comments.user',
            'attachments.uploader',
            'auditLogs.user',
            'histories.previousStatus',
            'histories.newStatus',
            'histories.changedBy',
        ]);

        $orderedComments = $ticket->comments->sortByDesc('created_at')->values();

        $publicComments = $orderedComments
            ->where('is_internal', false)
            ->values();

        $internalComments = collect();

        if ($user->isAgent() || $user->isAdmin()) {
            $internalComments = $orderedComments
                ->where('is_internal', true)
                ->values();
        }

        $publicAttachments = $ticket->attachments
            ->where('is_internal', false)
            ->sortByDesc('created_at')
            ->values();

        $internalAttachments = collect();

        if ($user->isAgent() || $user->isAdmin()) {
            $internalAttachments = $ticket->attachments
                ->where('is_internal', true)
                ->sortByDesc('created_at')
                ->values();
        }

        $auditLogs = collect();

        if ($user->isAdmin()) {
            $auditLogs = $ticket->auditLogs
                ->sortByDesc('created_at')
                ->values();
        }

        $agents = User::whereHas('role', function ($q) {
            $q->where('name', 'Agente');
        })->orderBy('name')->get();

        $statuses = $this->visibleWorkflowStatuses($user);
        $priorities = Priority::orderBy('level')->get();

        return view('tickets.show', [
            'ticket' => $ticket,
            'agents' => $agents,
            'statuses' => $statuses,
            'priorities' => $priorities,
            'publicComments' => $publicComments,
            'internalComments' => $internalComments,
            'publicAttachments' => $publicAttachments,
            'internalAttachments' => $internalAttachments,
            'auditLogs' => $auditLogs,
            'impactOptions' => Ticket::IMPACT_LEVELS,
            'urgencyOptions' => Ticket::URGENCY_LEVELS,
            'isAdmin' => $user->isAdmin(),
            'isAgent' => $user->isAgent(),
            'canSelfAssign' => $user->isAgent() && is_null($ticket->assigned_to),
        ]);
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isUserRole()) {
            abort(403, 'No autorizado para actualizar tickets.');
        }

        if ($user->isAgent() && !is_null($ticket->assigned_to) && $ticket->assigned_to !== $user->id) {
            abort(403, 'No autorizado para actualizar este ticket.');
        }

        $rules = [
            'status_id' => ['nullable', 'exists:ticket_statuses,id'],
        ];

        if ($user->isAdmin()) {
            $rules['assigned_to'] = ['nullable', 'exists:users,id'];
            $rules['priority_id'] = ['nullable', 'exists:priorities,id'];
            $rules['impact'] = ['nullable', Rule::in(array_keys(Ticket::IMPACT_LEVELS))];
            $rules['urgency'] = ['nullable', Rule::in(array_keys(Ticket::URGENCY_LEVELS))];
        } elseif ($user->isAgent() && is_null($ticket->assigned_to) && $request->input('take_ticket') === '1') {
            $rules['take_ticket'] = ['nullable', 'in:1'];
        }

        $validated = $request->validate($rules);

        $newAssignedTo = null;

        if ($user->isAdmin()) {
            $newAssignedTo = $request->has('assigned_to') && !empty($validated['assigned_to'])
                ? (int) $validated['assigned_to']
                : null;

            if ($request->has('assigned_to') && !is_null($newAssignedTo)) {
                $assignee = User::with('role')->find($newAssignedTo);

                if (!$assignee || !$assignee->isAgent()) {
                    return redirect()
                        ->route('tickets.show', $ticket)
                        ->with('error', 'Solo se pueden asignar tickets a usuarios con rol Agente.');
                }
            }
        } elseif ($user->isAgent() && is_null($ticket->assigned_to) && $request->input('take_ticket') === '1') {
            $newAssignedTo = (int) $user->id;
        }

        if ($user->isAgent() && $request->hasAny(['assigned_to', 'priority_id', 'impact', 'urgency'])) {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('error', 'La asignación y clasificación interna solo puede modificarla el administrador.');
        }

        $previousStatusId = $ticket->status_id;
        $previousAssignedTo = $ticket->assigned_to;
        $previousPriorityId = $ticket->priority_id;
        $previousImpact = $ticket->impact;
        $previousUrgency = $ticket->urgency;

        $notes = [];
        $statusChanged = false;
        $assignmentChanged = false;
        $classificationChanged = false;

        DB::transaction(function () use (
            $request,
            $validated,
            $ticket,
            $user,
            $newAssignedTo,
            $previousStatusId,
            $previousAssignedTo,
            $previousPriorityId,
            $previousImpact,
            $previousUrgency,
            &$notes,
            &$statusChanged,
            &$assignmentChanged,
            &$classificationChanged
        ) {
            $assignmentRequested = $user->isAdmin() && $request->has('assigned_to');
            $selfAssignRequested = $user->isAgent() && is_null($previousAssignedTo) && $request->input('take_ticket') === '1';

            if (($assignmentRequested || $selfAssignRequested) && $ticket->assigned_to !== $newAssignedTo) {
                $ticket->assigned_to = $newAssignedTo;
                $assignmentChanged = true;

                if (is_null($previousAssignedTo) && !is_null($newAssignedTo)) {
                    $notes[] = $selfAssignRequested ? 'Ticket tomado por agente.' : 'Ticket asignado.';
                } elseif (!is_null($previousAssignedTo) && is_null($newAssignedTo)) {
                    $notes[] = 'Ticket desasignado.';
                } else {
                    $notes[] = 'Ticket reasignado.';
                }
            }

            if ($user->isAdmin() && $request->has('priority_id')) {
                $newPriorityId = !empty($validated['priority_id']) ? (int) $validated['priority_id'] : null;

                if ((int) ($ticket->priority_id ?? 0) !== (int) ($newPriorityId ?? 0)) {
                    $ticket->priority_id = $newPriorityId;
                    $ticket->priority_reviewed_at = now();
                    $classificationChanged = true;
                    $notes[] = $newPriorityId ? 'Prioridad técnica actualizada.' : 'Prioridad técnica retirada.';

                    $this->applySlaDates($ticket, $newPriorityId);
                }
            }

            if ($user->isAdmin() && $request->has('impact') && ($ticket->impact ?? '') !== ($validated['impact'] ?? null)) {
                $ticket->impact = $validated['impact'] ?? null;
                $classificationChanged = true;
                $notes[] = 'Impacto actualizado.';
            }

            if ($user->isAdmin() && $request->has('urgency') && ($ticket->urgency ?? '') !== ($validated['urgency'] ?? null)) {
                $ticket->urgency = $validated['urgency'] ?? null;
                $classificationChanged = true;
                $notes[] = 'Urgencia actualizada.';
            }

            if (!empty($validated['status_id']) && (int) $validated['status_id'] !== (int) $ticket->status_id) {
                $newStatus = TicketStatus::findOrFail((int) $validated['status_id']);

                $ticket->status_id = $newStatus->id;
                $statusChanged = true;
                $notes[] = 'Cambio de estado a ' . $newStatus->name . '.';

                $this->applyStatusDates($ticket, $newStatus);
            }

            if ($statusChanged || $assignmentChanged || $classificationChanged) {
                $ticket->save();

                TicketStatusHistory::create([
                    'ticket_id' => $ticket->id,
                    'previous_status_id' => $previousStatusId,
                    'new_status_id' => $ticket->status_id,
                    'changed_by' => $user->id,
                    'changed_at' => now(),
                    'notes' => !empty($notes) ? implode(' ', $notes) : 'Actualización de ticket.',
                ]);
            }
        });

        if (!$statusChanged && !$assignmentChanged && !$classificationChanged) {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', 'No se detectaron cambios.');
        }

        $ticket->refresh()->load(['status', 'assignee', 'creator']);
        $notificationService = app(InternalNotificationService::class);

        if ($assignmentChanged && $ticket->assigned_to) {
            $notificationService->createForUser(
                $ticket->assigned_to,
                $ticket,
                'Caso asignado',
                'Se te asignó una solicitud para seguimiento.',
                'info'
            );
        }

        if ($statusChanged) {
            $slug = strtolower((string) $ticket->status?->slug);

            if (in_array($slug, ['resuelto', 'en-espera-usuario', 'cerrado'], true)) {
                $title = match ($slug) {
                    'resuelto' => 'Solicitud atendida',
                    'en-espera-usuario' => 'Necesitamos tu respuesta',
                    'cerrado' => 'Solicitud finalizada',
                    default => 'Actualización de solicitud',
                };

                $message = match ($slug) {
                    'resuelto' => 'La solicitud tiene una solución registrada. Revísala y califica la atención.',
                    'en-espera-usuario' => 'Hay una actualización que requiere información adicional para continuar.',
                    'cerrado' => 'La solicitud fue finalizada correctamente.',
                    default => 'Hay una actualización disponible.',
                };

                $notificationService->createForUser($ticket->created_by, $ticket, $title, $message, $slug === 'en-espera-usuario' ? 'warning' : 'success');
            }
        }

        app(AuditLogger::class)->log('ticket.updated', 'Se actualizó el ticket.', $ticket, [
            'status_id' => $previousStatusId,
            'assigned_to' => $previousAssignedTo,
            'priority_id' => $previousPriorityId,
            'impact' => $previousImpact,
            'urgency' => $previousUrgency,
        ], [
            'status_id' => $ticket->status_id,
            'assigned_to' => $ticket->assigned_to,
            'priority_id' => $ticket->priority_id,
            'impact' => $ticket->impact,
            'urgency' => $ticket->urgency,
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', $user->isAdmin() ? 'Ticket actualizado correctamente.' : 'Avance actualizado correctamente.');
    }

    public function acceptSolution(Request $request, Ticket $ticket): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isUserRole() || $ticket->created_by !== $user->id) {
            abort(403, 'Solo el solicitante puede finalizar esta solicitud.');
        }

        if (!$ticket->status || strtolower((string) $ticket->status->slug) !== 'resuelto') {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('error', 'Solo puedes finalizar una solicitud con solución registrada.');
        }

        $validated = $request->validate([
            'satisfaction_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'satisfaction_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $closedStatus = TicketStatus::where('slug', 'cerrado')->firstOrFail();
        $previousStatusId = $ticket->status_id;

        $ticket->update([
            'status_id' => $closedStatus->id,
            'closed_at' => now(),
            'satisfaction_rating' => $validated['satisfaction_rating'] ?? null,
            'satisfaction_comment' => $validated['satisfaction_comment'] ?? null,
            'satisfaction_submitted_at' => now(),
        ]);

        TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'previous_status_id' => $previousStatusId,
            'new_status_id' => $closedStatus->id,
            'changed_by' => $user->id,
            'changed_at' => now(),
            'notes' => 'El solicitante aceptó la solución y finalizó la solicitud.',
        ]);

        app(AuditLogger::class)->log('ticket.satisfaction_submitted', 'El solicitante calificó y finalizó la solicitud.', $ticket, null, [
            'satisfaction_rating' => $ticket->satisfaction_rating,
        ]);

        if ($ticket->assigned_to) {
            app(InternalNotificationService::class)->createForUser(
                $ticket->assigned_to,
                $ticket,
                'Solicitud finalizada por el usuario',
                'El solicitante confirmó la solución y calificó la atención.',
                'success'
            );
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Gracias por confirmar. Tu solicitud fue finalizada y movida a tu historial.');
    }

    public function reopen(Request $request, Ticket $ticket): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isUserRole() && $ticket->created_by !== $user->id) {
            abort(403, 'No autorizado para reabrir este ticket.');
        }

        if ($user->isUserRole() && !$ticket->canRequesterRequestReview()) {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('error', 'La revisión adicional solo está disponible cuando existe una solución registrada. Una vez finalizada la solicitud, ya no puede reabrirse desde esta sección.');
        }

        if ($user->isAgent() && !is_null($ticket->assigned_to) && $ticket->assigned_to !== $user->id) {
            abort(403, 'No autorizado para reabrir este ticket.');
        }

        if (($user->isAgent() || $user->isAdmin()) && !$ticket->isResolved() && !$ticket->isFinalized()) {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('error', 'Solo se pueden reabrir solicitudes con solución registrada o finalizadas.');
        }

        $validated = $request->validate([
            'reopen_reason' => ['required', 'string', 'max:1000'],
        ]);

        $reopenStatus = TicketStatus::where('slug', 'reabierto')->first()
            ?? TicketStatus::where('slug', 'en-proceso')->firstOrFail();

        $previousStatusId = $ticket->status_id;

        $ticket->update([
            'status_id' => $reopenStatus->id,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'previous_status_id' => $previousStatusId,
            'new_status_id' => $reopenStatus->id,
            'changed_by' => $user->id,
            'changed_at' => now(),
            'notes' => 'Ticket reabierto. Motivo: ' . $validated['reopen_reason'],
        ]);

        app(AuditLogger::class)->log('ticket.reopened', 'La solicitud fue reabierta.', $ticket, null, [
            'reason' => $validated['reopen_reason'],
        ]);

        if ($ticket->assigned_to && $ticket->assigned_to !== $user->id) {
            app(InternalNotificationService::class)->createForUser(
                $ticket->assigned_to,
                $ticket,
                'Solicitud reabierta',
                'El solicitante pidió una revisión adicional.',
                'warning'
            );
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', $user->isUserRole() ? 'Tu solicitud fue enviada a revisión adicional. El equipo de soporte dará seguimiento.' : 'Ticket reabierto correctamente.');
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin()) {
            abort(403, 'No autorizado para eliminar tickets.');
        }

        app(AuditLogger::class)->log('ticket.deleted', 'Ticket eliminado por administrador.', $ticket, [
            'folio' => $ticket->folio,
            'subject' => $ticket->subject,
        ]);

        $ticket->delete();

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Ticket eliminado correctamente.');
    }

    private function generateFolio(): string
    {
        do {
            $folio = 'HD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Ticket::where('folio', $folio)->exists());

        return $folio;
    }

    private function getBestAvailableAgent(): ?User
    {
        $agents = User::whereHas('role', function ($q) {
            $q->where('name', 'Agente');
        })
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($agents->isEmpty()) {
            return null;
        }

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $agentsWithLoad = $agents->map(function (User $agent) use ($todayStart, $todayEnd) {
            $activeTickets = Ticket::with(['priority', 'status', 'creator'])
                ->where('assigned_to', $agent->id)
                ->whereHas('status', function ($q) {
                    $q->where('is_closed', false)
                        ->where('slug', '!=', 'resuelto');
                })
                ->get();

            $activeTicketsCount = $activeTickets->count();
            $weightedLoad = $activeTickets->sum(function (Ticket $ticket) {
                return $this->getPriorityWeight($ticket) + (int) (($ticket->creator?->attention_weight ?? 20) / 20);
            });

            $assignmentsToday = Ticket::where('assigned_to', $agent->id)
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->count();

            $lastAssignedAt = Ticket::where('assigned_to', $agent->id)
                ->latest('created_at')
                ->value('created_at');

            return [
                'agent' => $agent,
                'agent_id' => $agent->id,
                'weighted_load' => $weightedLoad,
                'active_tickets' => $activeTicketsCount,
                'assignments_today' => $assignmentsToday,
                'last_assigned_at_timestamp' => $lastAssignedAt ? strtotime((string) $lastAssignedAt) : 0,
                'has_capacity' => $activeTicketsCount < self::MAX_ACTIVE_TICKETS_PER_AGENT,
            ];
        });

        $selected = $agentsWithLoad
            ->filter(fn ($item) => $item['has_capacity'] === true)
            ->sortBy([
                ['active_tickets', 'asc'],
                ['weighted_load', 'asc'],
                ['assignments_today', 'asc'],
                ['last_assigned_at_timestamp', 'asc'],
                ['agent_id', 'asc'],
            ])
            ->first();

        return $selected['agent'] ?? null;
    }

    private function getPriorityWeight(Ticket $ticket): int
    {
        $level = $ticket->priority?->level;

        return is_numeric($level) ? (int) $level : 0;
    }

    private function applySlaDates(Ticket $ticket, ?int $priorityId): void
    {
        $priority = $priorityId ? Priority::find($priorityId) : null;

        app(TicketClassificationService::class)->applyResponseTimes($ticket, $priority?->level);
    }

    private function applyStatusDates(Ticket $ticket, TicketStatus $status): void
    {
        $slug = strtolower((string) $status->slug);

        if ($slug === 'resuelto' || $status->is_closed) {
            $ticket->resolved_at ??= now();
        } else {
            $ticket->resolved_at = null;
        }

        if ($status->is_closed) {
            $ticket->closed_at ??= now();
        } else {
            $ticket->closed_at = null;
        }
    }


    private function visibleWorkflowStatuses(User $user): \Illuminate\Support\Collection
    {
        $slugs = $user->isAgent()
            ? ['en-revision', 'en-proceso', 'en-espera-usuario', 'en-espera-proveedor', 'resuelto']
            : ['nuevo', 'en-revision', 'en-proceso', 'en-espera-usuario', 'en-espera-proveedor', 'resuelto', 'reabierto', 'cerrado', 'cancelado'];

        return TicketStatus::whereIn('slug', $slugs)
            ->orderBy('sort_order')
            ->get()
            ->sortBy(fn (TicketStatus $status) => array_search($status->slug, $slugs, true))
            ->values();
    }

    /**
     * Guarda la evidencia inicial de la solicitud.
     *
     * Estos archivos forman parte del registro original del caso. Por integridad del
     * seguimiento, el solicitante no podrá agregarlos ni eliminarlos después de enviar la solicitud.
     *
     * @return array<int, string>
     */
    private function storeInitialSupportFiles(Request $request, Ticket $ticket, User $user): array
    {
        $uploaded = [];

        $files = $request->file('support_files', []);

        if ($files instanceof \Illuminate\Http\UploadedFile) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $storedName = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('ticket_attachments/' . $ticket->id, $storedName, 'local');

            TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'uploaded_by' => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedName,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize() ?: 0,
                'is_internal' => false,
            ]);

            $uploaded[] = $file->getClientOriginalName();
        }

        return $uploaded;
    }

    private function authorizeTicketAccess(Ticket $ticket): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isAgent()) {
            if ($ticket->assigned_to === $user->id || is_null($ticket->assigned_to)) {
                return;
            }

            abort(403, 'No autorizado para ver este ticket.');
        }

        if ($user->isUserRole() && $ticket->created_by === $user->id) {
            return;
        }

        abort(403, 'No autorizado para ver este ticket.');
    }
}
