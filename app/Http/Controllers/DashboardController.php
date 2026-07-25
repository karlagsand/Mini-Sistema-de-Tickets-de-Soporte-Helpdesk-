<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\InternalNotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }

        if ($user->isAgent()) {
            return $this->agentDashboard($user);
        }

        return $this->userDashboard($user);
    }

    public function adminReport(): View
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin()) {
            abort(403, 'No autorizado para exportar este reporte.');
        }

        return view('dashboard.admin-report', $this->adminDashboardData() + [
            'generatedAt' => now(),
        ]);
    }

    public function adminReportPdf(): Response
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin()) {
            abort(403, 'No autorizado para exportar este reporte.');
        }

        $data = $this->adminDashboardData() + [
            'generatedAt' => now(),
        ];

        if (!class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            return response(
                view('reports.admin-executive-pdf', $data),
                200,
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.admin-executive-pdf', $data)
            ->setPaper('letter', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        return $pdf->download('reporte-mesa-ayuda-' . now()->format('Ymd-His') . '.pdf');
    }

    private function adminDashboard(): View
    {
        return view('dashboard.admin', $this->adminDashboardData());
    }

    private function adminDashboardData(): array
    {
        $tickets = Ticket::with([
            'status',
            'priority',
            'category',
            'creator.role',
            'assignee.role',
        ])->latest()->get();

        $openTickets = $tickets->filter(fn (Ticket $ticket) => !$this->isClosedStatus($ticket->status));
        $activeTickets = $tickets->filter(fn (Ticket $ticket) => $this->isActiveStatus($ticket->status));
        $resolvedTickets = $tickets->filter(fn (Ticket $ticket) => $this->isResolvedStatus($ticket->status));
        $closedTickets = $tickets->filter(fn (Ticket $ticket) => $this->isClosedStatus($ticket->status));
        $newTickets = $tickets->filter(fn (Ticket $ticket) => $this->isNewStatus($ticket->status));
        $unassignedTicketsCollection = $activeTickets->filter(fn (Ticket $ticket) => is_null($ticket->assigned_to));
        $overdueTickets = $activeTickets->filter(fn (Ticket $ticket) => $this->isOverdueTicket($ticket))->values();
        $dueSoonTickets = $activeTickets->filter(fn (Ticket $ticket) => $this->isDueSoonTicket($ticket))->values();
        $withoutRecentUpdate = $activeTickets
            ->filter(fn (Ticket $ticket) => $ticket->updated_at && $ticket->updated_at->lt(now()->subHours(24)))
            ->values();

        $todayTickets = $tickets->filter(fn (Ticket $ticket) => $ticket->created_at && $ticket->created_at->isToday());
        $todayResolved = $resolvedTickets->filter(fn (Ticket $ticket) => $ticket->resolved_at && $ticket->resolved_at->isToday());

        $avgFirstResponseHours = round($this->calculateAverageHours($tickets, 'opened_at', 'first_responded_at'), 2);
        $avgResolutionHours = round($this->calculateAverageHours($resolvedTickets, 'opened_at', 'resolved_at'), 2);
        $avgClosureHours = round($this->calculateAverageHours($closedTickets, 'opened_at', 'closed_at'), 2);

        $responseEvaluated = $tickets->filter(fn (Ticket $ticket) => $ticket->first_response_due_at && $ticket->first_responded_at);
        $responseOnTime = $responseEvaluated->filter(fn (Ticket $ticket) => $ticket->first_responded_at->lessThanOrEqualTo($ticket->first_response_due_at));
        $responseCompliance = $responseEvaluated->count() > 0
            ? round(($responseOnTime->count() / $responseEvaluated->count()) * 100, 1)
            : 0;

        $solutionEvaluated = $tickets->filter(function (Ticket $ticket) {
            return $ticket->resolution_due_at && ($ticket->resolved_at || $ticket->closed_at);
        });
        $solutionOnTime = $solutionEvaluated->filter(function (Ticket $ticket) {
            $finishedAt = $ticket->closed_at ?? $ticket->resolved_at;
            return $finishedAt && $finishedAt->lessThanOrEqualTo($ticket->resolution_due_at);
        });
        $solutionCompliance = $solutionEvaluated->count() > 0
            ? round(($solutionOnTime->count() / $solutionEvaluated->count()) * 100, 1)
            : 0;

        $resolutionRate = $tickets->count() > 0
            ? round(($resolvedTickets->count() / $tickets->count()) * 100, 1)
            : 0;

        $closureRate = $tickets->count() > 0
            ? round(($closedTickets->count() / $tickets->count()) * 100, 1)
            : 0;

        $ratedTickets = $tickets->filter(fn (Ticket $ticket) => !is_null($ticket->satisfaction_rating));
        $avgSatisfaction = $ratedTickets->count() > 0
            ? round((float) $ratedTickets->avg('satisfaction_rating'), 2)
            : 0;

        $agents = User::whereHas('role', function ($q) {
            $q->where('name', 'Agente');
        })->orderBy('name')->get();

        $agentsLoad = $agents->map(function (User $agent) {
            $agentTickets = Ticket::with(['priority', 'status'])
                ->where('assigned_to', $agent->id)
                ->get();

            $activeAgentTickets = $agentTickets
                ->filter(fn (Ticket $ticket) => $this->isActiveStatus($ticket->status))
                ->values();

            $resolvedAgentTickets = $agentTickets
                ->filter(fn (Ticket $ticket) => $this->isResolvedStatus($ticket->status))
                ->values();

            $closedAgentTickets = $agentTickets
                ->filter(fn (Ticket $ticket) => $this->isClosedStatus($ticket->status))
                ->values();

            $finishedAgentTickets = $agentTickets
                ->filter(function (Ticket $ticket) {
                    return $this->isResolvedStatus($ticket->status)
                        || $this->isClosedStatus($ticket->status)
                        || !is_null($ticket->resolved_at)
                        || !is_null($ticket->closed_at);
                })
                ->values();

            $overdueAgentTickets = $activeAgentTickets
                ->filter(fn (Ticket $ticket) => $this->isOverdueTicket($ticket))
                ->values();

            $ratedAgentTickets = $agentTickets
                ->filter(fn (Ticket $ticket) => !is_null($ticket->satisfaction_rating))
                ->values();

            $avgAgentSatisfaction = $ratedAgentTickets->count() > 0
                ? round((float) $ratedAgentTickets->avg('satisfaction_rating'), 2)
                : 0;

            $weightedLoad = $activeAgentTickets->sum(function (Ticket $ticket) {
                return $this->getPriorityWeight($ticket->priority?->name, $ticket->priority?->slug);
            });

            $avgAgentResolution = round(
                $this->calculateAverageHours($finishedAgentTickets, 'opened_at', 'resolved_at'),
                2
            );

            $agentResponseEvaluated = $agentTickets->filter(
                fn (Ticket $ticket) => $ticket->first_response_due_at && $ticket->first_responded_at
            );

            $agentResponseOnTime = $agentResponseEvaluated->filter(
                fn (Ticket $ticket) => $ticket->first_responded_at->lessThanOrEqualTo($ticket->first_response_due_at)
            );

            $agentResponseCompliance = $agentResponseEvaluated->count() > 0
                ? round(($agentResponseOnTime->count() / $agentResponseEvaluated->count()) * 100, 1)
                : 0;

            $agentSolutionEvaluated = $agentTickets->filter(function (Ticket $ticket) {
                return $ticket->resolution_due_at && ($ticket->resolved_at || $ticket->closed_at);
            });

            $agentSolutionOnTime = $agentSolutionEvaluated->filter(function (Ticket $ticket) {
                $finishedAt = $ticket->closed_at ?? $ticket->resolved_at;

                return $finishedAt && $finishedAt->lessThanOrEqualTo($ticket->resolution_due_at);
            });

            $agentSolutionCompliance = $agentSolutionEvaluated->count() > 0
                ? round(($agentSolutionOnTime->count() / $agentSolutionEvaluated->count()) * 100, 1)
                : 0;

            $activeCount = $activeAgentTickets->count();
            $resolvedCount = $resolvedAgentTickets->count();
            $closedCount = $closedAgentTickets->count();
            $overdueCount = $overdueAgentTickets->count();

            return [
                'id' => $agent->id,
                'name' => $agent->name,
                'email' => $agent->email,

                // Llaves principales que usa el dashboard
                'total_tickets' => $agentTickets->count(),
                'active_tickets' => $activeCount,
                'resolved_tickets' => $resolvedCount,
                'closed_tickets' => $closedCount,
                'finished_tickets' => $finishedAgentTickets->count(),
                'overdue_tickets' => $overdueCount,
                'weighted_load' => $weightedLoad,
                'avg_resolution_hours' => $avgAgentResolution,
                'response_compliance' => $agentResponseCompliance,
                'solution_compliance' => $agentSolutionCompliance,
                'satisfaction_average' => $avgAgentSatisfaction,
                'satisfaction_count' => $ratedAgentTickets->count(),
                'load_level' => $this->getLoadLevel($weightedLoad, $activeCount),

                // Alias para que el PDF también pueda leerlos aunque use nombres anteriores
                'active' => $activeCount,
                'resolved' => $resolvedCount,
                'closed' => $closedCount,
                'overdue' => $overdueCount,
                'satisfaction' => $avgAgentSatisfaction,
                'ratings_count' => $ratedAgentTickets->count(),
            ];
        })->sortByDesc('weighted_load')->values();

        $ticketsByStatus = $this->groupCount($tickets, fn (Ticket $ticket) => $this->statusLabel($ticket->status));
        $ticketsByPriority = $this->groupCount($tickets, fn (Ticket $ticket) => $ticket->priority->name ?? 'Sin prioridad');
        $ticketsByCategory = $this->groupCount($tickets, fn (Ticket $ticket) => $ticket->category->name ?? 'Sin área')->take(7);
        $ticketsByRequestType = $this->groupCount($tickets, fn (Ticket $ticket) => $ticket->requestTypeLabel());
        $ticketsByUserLevel = $this->groupCount($tickets, fn (Ticket $ticket) => $ticket->creator?->attentionLabel() ?? 'Operativo');
        $ticketsBySlaState = $this->groupCount($tickets, fn (Ticket $ticket) => $this->slaStateLabel($ticket));

        $oldestOpenTickets = $openTickets
            ->sortBy('opened_at')
            ->take(5)
            ->values();

        $topRiskTickets = $activeTickets
            ->sortByDesc(fn (Ticket $ticket) => $this->ticketWorkScore($ticket))
            ->take(6)
            ->values();

        $highLevelPendingTickets = $activeTickets
            ->filter(fn (Ticket $ticket) => (int) ($ticket->creator?->attention_weight ?? 20) >= 80)
            ->sortByDesc(fn (Ticket $ticket) => $this->ticketWorkScore($ticket))
            ->take(5)
            ->values();

        $recentTickets = $tickets
            ->take(8)
            ->values();

        $ticketsCreatedByDay = collect(range(13, 0))->map(function ($daysAgo) use ($tickets) {
            $date = now()->subDays($daysAgo);

            return [
                'label' => $date->format('d/m'),
                'count' => $tickets->filter(function (Ticket $ticket) use ($date) {
                    return $ticket->created_at && $ticket->created_at->isSameDay($date);
                })->count(),
            ];
        })->values();

        $opportunities = $this->buildAdminOpportunities(
            $tickets,
            $activeTickets,
            $unassignedTicketsCollection,
            $overdueTickets,
            $dueSoonTickets,
            $withoutRecentUpdate,
            $ticketsByCategory,
            $agentsLoad,
            $avgSatisfaction,
            $responseCompliance,
            $solutionCompliance
        );

        $alerts = collect();

        if ($unassignedTicketsCollection->count() > 0) {
            $alerts->push([
                'type' => 'danger',
                'title' => 'Casos sin responsable',
                'message' => 'Hay ' . $unassignedTicketsCollection->count() . ' casos activos sin responsable asignado.',
            ]);
        }

        if ($overdueTickets->count() > 0) {
            $alerts->push([
                'type' => 'danger',
                'title' => 'Tiempos vencidos',
                'message' => 'Hay ' . $overdueTickets->count() . ' casos con tiempo de respuesta o solución vencido.',
            ]);
        }

        if ($dueSoonTickets->count() > 0) {
            $alerts->push([
                'type' => 'warning',
                'title' => 'Próximos a vencer',
                'message' => $dueSoonTickets->count() . ' casos requieren atención durante las próximas horas.',
            ]);
        }

        if ($agentsLoad->first() && $agentsLoad->first()['load_level'] === 'high') {
            $alerts->push([
                'type' => 'info',
                'title' => 'Carga elevada',
                'message' => 'Uno o más agentes presentan carga alta. Conviene revisar balance o reasignación.',
            ]);
        }

        $currentUser = Auth::user();
        $notificationService = app(InternalNotificationService::class);

        return [
            'internalNotifications' => $currentUser ? $notificationService->unreadForUser($currentUser, 8) : collect(),
            'unreadNotifications' => $currentUser ? $notificationService->unreadCount($currentUser) : 0,
            'totalTickets' => $tickets->count(),
            'openTickets' => $openTickets->count(),
            'activeTickets' => $activeTickets->count(),
            'resolvedTickets' => $resolvedTickets->count(),
            'closedTickets' => $closedTickets->count(),
            'newTickets' => $newTickets->count(),
            'unassignedTickets' => $unassignedTicketsCollection->count(),
            'overdueTickets' => $overdueTickets->count(),
            'dueSoonTickets' => $dueSoonTickets->count(),
            'withoutRecentUpdate' => $withoutRecentUpdate->count(),
            'todayTickets' => $todayTickets->count(),
            'todayResolved' => $todayResolved->count(),
            'avgFirstResponseHours' => $avgFirstResponseHours,
            'avgResolutionHours' => $avgResolutionHours,
            'avgClosureHours' => $avgClosureHours,
            'responseCompliance' => $responseCompliance,
            'solutionCompliance' => $solutionCompliance,
            'resolutionRate' => $resolutionRate,
            'closureRate' => $closureRate,
            'avgSatisfaction' => $avgSatisfaction,
            'ratedTicketsCount' => $ratedTickets->count(),
            'agentsLoad' => $agentsLoad,
            'ticketsByStatus' => $ticketsByStatus,
            'ticketsByPriority' => $ticketsByPriority,
            'ticketsByCategory' => $ticketsByCategory,
            'ticketsByRequestType' => $ticketsByRequestType,
            'ticketsByUserLevel' => $ticketsByUserLevel,
            'ticketsBySlaState' => $ticketsBySlaState,
            'oldestOpenTickets' => $oldestOpenTickets,
            'topRiskTickets' => $topRiskTickets,
            'highLevelPendingTickets' => $highLevelPendingTickets,
            'recentTickets' => $recentTickets,
            'generatedAt' => now(),
            'ticketsCreatedByDay' => $ticketsCreatedByDay,
            'opportunities' => $opportunities,
            'alerts' => $alerts,
        ];
    }

    private function agentDashboard(User $user): View
    {
        $myTickets = Ticket::with([
            'status',
            'priority',
            'category',
            'creator.role',
        ])
            ->where('assigned_to', $user->id)
            ->latest('updated_at')
            ->get();

        $activeTickets = $myTickets->filter(fn (Ticket $ticket) => $this->isActiveStatus($ticket->status))->values();
        $resolvedTickets = $myTickets->filter(fn (Ticket $ticket) => $this->isResolvedStatus($ticket->status))->values();
        $closedTickets = $myTickets->filter(fn (Ticket $ticket) => $this->isClosedStatus($ticket->status))->values();

        $urgentTickets = $activeTickets->filter(function (Ticket $ticket) {
            return $this->getPriorityWeight($ticket->priority?->name, $ticket->priority?->slug) >= 3;
        })->values();

        $newAssignedTickets = $activeTickets->filter(function (Ticket $ticket) {
            $slug = $this->statusSlug($ticket->status);

            return in_array($slug, ['nuevo', 'asignado', 'en-revision', 'reabierto'], true)
                || ($ticket->created_at && $ticket->created_at->gte(now()->subDay()));
        })->values();

        $overdueTickets = $activeTickets->filter(fn (Ticket $ticket) => $this->isOverdueTicket($ticket))->values();
        $dueSoonTickets = $activeTickets->filter(fn (Ticket $ticket) => $this->isDueSoonTicket($ticket))->values();

        $availableTickets = Ticket::with(['status', 'priority', 'category', 'creator.role'])
            ->whereNull('assigned_to')
            ->latest('updated_at')
            ->get()
            ->filter(fn (Ticket $ticket) => $this->isActiveStatus($ticket->status))
            ->values();

        $weightedLoad = $activeTickets->sum(function (Ticket $ticket) {
            return $this->getPriorityWeight($ticket->priority?->name, $ticket->priority?->slug);
        });

        $recentMyTickets = $activeTickets
            ->sortByDesc('updated_at')
            ->take(6)
            ->values();

        $recentAvailableTickets = $availableTickets
            ->sortByDesc(fn (Ticket $ticket) => $this->ticketWorkScore($ticket))
            ->take(6)
            ->values();

        $priorityQueue = $activeTickets
            ->merge($availableTickets)
            ->unique('id')
            ->sortByDesc(fn (Ticket $ticket) => $this->ticketWorkScore($ticket))
            ->take(7)
            ->values();

        $avgMyResolutionHours = round($this->calculateAverageHours($resolvedTickets, 'opened_at', 'resolved_at'), 2);

        $agentStatusCounts = $this->groupCount($myTickets, fn (Ticket $ticket) => $this->statusLabel($ticket->status));
        $agentPriorityCounts = $this->groupCount($myTickets, fn (Ticket $ticket) => $ticket->priority->name ?? 'Sin prioridad');

        $agentResolvedByDay = collect(range(6, 0))->map(function ($daysAgo) use ($resolvedTickets) {
            $date = now()->subDays($daysAgo);

            return [
                'label' => $date->format('d/m'),
                'count' => $resolvedTickets->filter(function (Ticket $ticket) use ($date) {
                    return $ticket->resolved_at && $ticket->resolved_at->isSameDay($date);
                })->count(),
            ];
        })->values();

        $agentResponseEvaluated = $myTickets->filter(fn (Ticket $ticket) => $ticket->first_response_due_at && $ticket->first_responded_at);
        $agentResponseOnTime = $agentResponseEvaluated->filter(fn (Ticket $ticket) => $ticket->first_responded_at->lessThanOrEqualTo($ticket->first_response_due_at));
        $agentResponseCompliance = $agentResponseEvaluated->count() > 0
            ? round(($agentResponseOnTime->count() / $agentResponseEvaluated->count()) * 100, 1)
            : 0;

        $agentSolutionEvaluated = $myTickets->filter(function (Ticket $ticket) {
            return $ticket->resolution_due_at && ($ticket->resolved_at || $ticket->closed_at);
        });
        $agentSolutionOnTime = $agentSolutionEvaluated->filter(function (Ticket $ticket) {
            $finishedAt = $ticket->closed_at ?? $ticket->resolved_at;
            return $finishedAt && $finishedAt->lessThanOrEqualTo($ticket->resolution_due_at);
        });
        $agentSolutionCompliance = $agentSolutionEvaluated->count() > 0
            ? round(($agentSolutionOnTime->count() / $agentSolutionEvaluated->count()) * 100, 1)
            : 0;

        $agentRatedTickets = $myTickets->filter(fn (Ticket $ticket) => !is_null($ticket->satisfaction_rating));
        $agentAvgSatisfaction = $agentRatedTickets->count() > 0
            ? round((float) $agentRatedTickets->avg('satisfaction_rating'), 2)
            : 0;

        $agentNotifications = collect();

        if ($newAssignedTickets->count() > 0) {
            $agentNotifications->push([
                'type' => 'primary',
                'title' => 'Tienes casos nuevos',
                'message' => $newAssignedTickets->count() . ' caso(s) asignado(s) requieren revisión inicial.',
                'url' => route('tickets.index', ['assignment' => 'mis_tickets', 'order' => 'cola']),
                'action' => 'Revisar casos',
            ]);
        }

        if ($overdueTickets->count() > 0) {
            $agentNotifications->push([
                'type' => 'danger',
                'title' => 'Casos vencidos',
                'message' => $overdueTickets->count() . ' caso(s) superaron su tiempo objetivo.',
                'url' => route('tickets.index', ['time_status' => 'vencido']),
                'action' => 'Atender ahora',
            ]);
        }

        if ($dueSoonTickets->count() > 0) {
            $agentNotifications->push([
                'type' => 'warning',
                'title' => 'Próximos a vencer',
                'message' => $dueSoonTickets->count() . ' caso(s) requieren atención durante las próximas horas.',
                'url' => route('tickets.index', ['time_status' => 'por_vencer']),
                'action' => 'Revisar prioridad',
            ]);
        }

        if ($activeTickets->count() === 0 && $availableTickets->count() === 0) {
            $agentNotifications->push([
                'type' => 'success',
                'title' => 'Bandeja al día',
                'message' => 'No tienes casos activos ni casos disponibles pendientes.',
                'url' => route('tickets.index'),
                'action' => 'Ver bandeja',
            ]);
        }

        $notificationService = app(InternalNotificationService::class);

        return view('dashboard.agent', [
            'internalNotifications' => $notificationService->unreadForUser($user, 8),
            'unreadNotifications' => $notificationService->unreadCount($user),
            'totalTickets' => $myTickets->count(),
            'activeTickets' => $activeTickets->count(),
            'resolvedTickets' => $resolvedTickets->count(),
            'closedTickets' => $closedTickets->count(),
            'urgentTickets' => $urgentTickets->count(),
            'newAssignedTickets' => $newAssignedTickets->count(),
            'availableTicketsCount' => $availableTickets->count(),
            'overdueTickets' => $overdueTickets->count(),
            'dueSoonTickets' => $dueSoonTickets->count(),
            'weightedLoad' => $weightedLoad,
            'avgMyResolutionHours' => $avgMyResolutionHours,
            'recentMyTickets' => $recentMyTickets,
            'recentAvailableTickets' => $recentAvailableTickets,
            'priorityQueue' => $priorityQueue,
            'agentStatusCounts' => $agentStatusCounts,
            'agentPriorityCounts' => $agentPriorityCounts,
            'agentResolvedByDay' => $agentResolvedByDay,
            'agentResponseCompliance' => $agentResponseCompliance,
            'agentSolutionCompliance' => $agentSolutionCompliance,
            'agentAvgSatisfaction' => $agentAvgSatisfaction,
            'agentRatedTicketsCount' => $agentRatedTickets->count(),
            'agentNotifications' => $agentNotifications,
            'loadLevel' => $this->getLoadLevel($weightedLoad, $activeTickets->count()),
        ]);
    }

    private function userDashboard(User $user): View
    {
        $myTickets = Ticket::with([
            'status',
            'category',
            'comments' => function ($query) {
                $query->where('is_internal', false)->with('user')->latest();
            },
        ])
            ->where('created_by', $user->id)
            ->latest('updated_at')
            ->get();

        $openTickets = $myTickets->filter(fn (Ticket $ticket) => !$this->isClosedStatus($ticket->status));
        $activeTickets = $myTickets->filter(fn (Ticket $ticket) => $this->isActiveStatus($ticket->status));
        $resolvedTickets = $myTickets->filter(fn (Ticket $ticket) => $this->isResolvedStatus($ticket->status));
        $closedTickets = $myTickets->filter(fn (Ticket $ticket) => $this->isClosedStatus($ticket->status));

        $updates = $myTickets->map(function (Ticket $ticket) use ($user) {
            $slug = $this->statusSlug($ticket->status);
            $latestPublicComment = $ticket->comments->first(fn ($comment) => $comment->user_id !== $user->id);

            if ($slug === 'en-espera-usuario') {
                return [
                    'ticket' => $ticket,
                    'title' => 'Necesitamos tu respuesta',
                    'message' => 'Hay una actualización que requiere información adicional para continuar.',
                    'label' => 'Responder',
                    'priority' => 4,
                    'date' => $ticket->updated_at,
                ];
            }

            if ($slug === 'resuelto' && is_null($ticket->satisfaction_submitted_at)) {
                return [
                    'ticket' => $ticket,
                    'title' => 'Tu solicitud tiene una solución registrada',
                    'message' => 'Revisa la solución registrada y confirma si la solicitud quedó atendida.',
                    'label' => 'Revisar solución',
                    'priority' => 3,
                    'date' => $ticket->resolved_at ?? $ticket->updated_at,
                ];
            }

            if ($latestPublicComment && $latestPublicComment->created_at && $latestPublicComment->created_at->gte(now()->subDays(10))) {
                return [
                    'ticket' => $ticket,
                    'title' => 'Hay una actualización',
                    'message' => str($latestPublicComment->comment)->limit(110)->toString(),
                    'label' => 'Ver seguimiento',
                    'priority' => 2,
                    'date' => $latestPublicComment->created_at,
                ];
            }

            return null;
        })->filter()->sortByDesc(function (array $update) {
            $timestamp = $update['date'] ? $update['date']->timestamp : 0;
            return ($update['priority'] * 10000000000) + $timestamp;
        })->take(5)->values();

        $recentTickets = $myTickets->take(6)->values();

        $notificationService = app(InternalNotificationService::class);

        return view('dashboard.user', [
            'internalNotifications' => $notificationService->unreadForUser($user, 8),
            'unreadNotifications' => $notificationService->unreadCount($user),
            'totalTickets' => $myTickets->count(),
            'openTickets' => $openTickets->count(),
            'activeTickets' => $activeTickets->count(),
            'resolvedTickets' => $resolvedTickets->count(),
            'closedTickets' => $closedTickets->count(),
            'updates' => $updates,
            'recentTickets' => $recentTickets,
        ]);
    }

    private function groupCount(Collection $tickets, callable $callback): Collection
    {
        return $tickets
            ->groupBy($callback)
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc();
    }

    private function calculateAverageHours(Collection $tickets, string $fromField, string $toField): float
    {
        $durations = $tickets->map(function (Ticket $ticket) use ($fromField, $toField) {
            $from = $ticket->{$fromField};
            $to = $ticket->{$toField};

            if (!$from || !$to) {
                return null;
            }

            return $from->diffInMinutes($to) / 60;
        })->filter(fn ($value) => !is_null($value));

        if ($durations->isEmpty()) {
            return 0;
        }

        return (float) $durations->avg();
    }

    private function getPriorityWeight(?string $priorityName, ?string $prioritySlug = null): int
    {
        $name = strtolower(trim((string) $priorityName));
        $slug = strtolower(trim((string) $prioritySlug));
        $value = $slug !== '' ? $slug : $name;

        return match ($value) {
            'baja', 'low' => 1,
            'media', 'normal', 'medium' => 2,
            'alta', 'high' => 3,
            'urgente', 'urgencia', 'critica', 'crítica', 'critical', 'urgent' => 4,
            default => 0,
        };
    }

    private function getLoadLevel(int|float $weightedLoad, int $activeTickets): string
    {
        if ($weightedLoad >= 12 || $activeTickets >= 8) {
            return 'high';
        }

        if ($weightedLoad >= 6 || $activeTickets >= 4) {
            return 'medium';
        }

        return 'low';
    }

    private function ticketWorkScore(Ticket $ticket): int
    {
        $score = 0;

        if ($this->isOverdueTicket($ticket)) {
            $score += 1000;
        } elseif ($this->isDueSoonTicket($ticket)) {
            $score += 700;
        }

        $score += $this->getPriorityWeight($ticket->priority?->name, $ticket->priority?->slug) * 120;
        $score += (int) ($ticket->creator?->attention_weight ?? 20);

        if (is_null($ticket->assigned_to)) {
            $score += 35;
        }

        return $score;
    }

    private function buildAdminOpportunities(
        Collection $tickets,
        Collection $activeTickets,
        Collection $unassignedTickets,
        Collection $overdueTickets,
        Collection $dueSoonTickets,
        Collection $withoutRecentUpdate,
        Collection $ticketsByCategory,
        Collection $agentsLoad,
        float $avgSatisfaction,
        float $responseCompliance,
        float $solutionCompliance
    ): Collection {
        $opportunities = collect();

        if ($overdueTickets->count() > 0) {
            $opportunities->push([
                'title' => 'Revisar casos vencidos',
                'message' => 'Los tiempos vencidos suelen indicar carga excesiva, falta de seguimiento o prioridad mal balanceada.',
                'action' => 'Ver vencidos',
                'url' => route('tickets.index', ['time_status' => 'vencido']),
            ]);
        }

        if ($unassignedTickets->count() > 0) {
            $opportunities->push([
                'title' => 'Asignar responsables',
                'message' => 'Los casos sin responsable aumentan el riesgo de retraso en la atención.',
                'action' => 'Ver sin responsable',
                'url' => route('tickets.index', ['assignment' => 'sin_asignar']),
            ]);
        }

        if ($dueSoonTickets->count() > 0) {
            $opportunities->push([
                'title' => 'Atender próximos vencimientos',
                'message' => 'Hay solicitudes que deben revisarse durante las próximas horas para evitar incumplimientos.',
                'action' => 'Ver por vencer',
                'url' => route('tickets.index', ['time_status' => 'por_vencer']),
            ]);
        }

        if ($withoutRecentUpdate->count() > 0) {
            $opportunities->push([
                'title' => 'Dar seguimiento a casos sin actualización',
                'message' => 'Algunos casos activos no han tenido movimiento reciente. Conviene solicitar avance a los responsables.',
                'action' => 'Ver activos',
                'url' => route('tickets.index', ['order' => 'cola']),
            ]);
        }

        $topCategory = $ticketsByCategory->keys()->first();
        $topCategoryCount = $ticketsByCategory->first();
        if ($topCategory && $topCategoryCount >= 3) {
            $opportunities->push([
                'title' => 'Analizar demanda por área',
                'message' => 'El área con más solicitudes es “' . $topCategory . '”. Puede requerir capacitación, documentación o mejora preventiva.',
                'action' => 'Filtrar tickets',
                'url' => route('tickets.index'),
            ]);
        }

        if ($agentsLoad->first() && $agentsLoad->first()['load_level'] === 'high') {
            $opportunities->push([
                'title' => 'Balancear carga de agentes',
                'message' => 'La carga no está distribuida de forma uniforme. Revisa reasignaciones o disponibilidad de agentes.',
                'action' => 'Supervisar tickets',
                'url' => route('tickets.index'),
            ]);
        }

        if ($tickets->count() > 0 && $responseCompliance > 0 && $responseCompliance < 85) {
            $opportunities->push([
                'title' => 'Mejorar tiempo de primera respuesta',
                'message' => 'El cumplimiento de primera respuesta está por debajo del objetivo sugerido de 85%.',
                'action' => 'Revisar tiempos',
                'url' => route('tickets.index', ['time_status' => 'vencido']),
            ]);
        }

        if ($tickets->count() > 0 && $solutionCompliance > 0 && $solutionCompliance < 85) {
            $opportunities->push([
                'title' => 'Mejorar tiempo de solución',
                'message' => 'El cumplimiento de solución está por debajo del objetivo sugerido de 85%.',
                'action' => 'Ver operación',
                'url' => route('tickets.index', ['order' => 'criticidad']),
            ]);
        }

        if ($avgSatisfaction > 0 && $avgSatisfaction < 4) {
            $opportunities->push([
                'title' => 'Revisar satisfacción del servicio',
                'message' => 'La calificación promedio está por debajo de 4.0. Conviene revisar comentarios de usuarios.',
                'action' => 'Ver tickets',
                'url' => route('tickets.index'),
            ]);
        }

        if ($opportunities->isEmpty() && $activeTickets->count() === 0) {
            $opportunities->push([
                'title' => 'Operación estable',
                'message' => 'No se detectan riesgos importantes en la mesa de ayuda en este momento.',
                'action' => 'Ver historial',
                'url' => route('tickets.index'),
            ]);
        }

        return $opportunities->take(6)->values();
    }

    private function slaStateLabel(Ticket $ticket): string
    {
        if (!$ticket->first_response_due_at && !$ticket->resolution_due_at) {
            return 'Sin tiempo calculado';
        }

        if ($this->isOverdueTicket($ticket)) {
            return 'Vencido';
        }

        if ($this->isDueSoonTicket($ticket)) {
            return 'Por vencer';
        }

        return 'En tiempo';
    }

    private function isOverdueTicket(Ticket $ticket): bool
    {
        $responseOverdue = is_null($ticket->first_responded_at)
            && !is_null($ticket->first_response_due_at)
            && $ticket->first_response_due_at->lt(now());

        $solutionOverdue = is_null($ticket->resolved_at)
            && is_null($ticket->closed_at)
            && !is_null($ticket->resolution_due_at)
            && $ticket->resolution_due_at->lt(now());

        return $responseOverdue || $solutionOverdue;
    }

    private function isDueSoonTicket(Ticket $ticket): bool
    {
        if ($this->isOverdueTicket($ticket)) {
            return false;
        }

        return is_null($ticket->resolved_at)
            && is_null($ticket->closed_at)
            && !is_null($ticket->resolution_due_at)
            && $ticket->resolution_due_at->between(now(), now()->copy()->addHours(8));
    }

    private function statusSlug(?TicketStatus $status): string
    {
        return strtolower((string) $status?->slug);
    }


    private function statusLabel(?TicketStatus $status): string
    {
        return match ($this->statusSlug($status)) {
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
    }

    private function isClosedStatus(?TicketStatus $status): bool
    {
        return (bool) $status?->is_closed || $this->statusSlug($status) === 'cerrado';
    }

    private function isResolvedStatus(?TicketStatus $status): bool
    {
        return $this->statusSlug($status) === 'resuelto';
    }

    private function isNewStatus(?TicketStatus $status): bool
    {
        return $this->statusSlug($status) === 'nuevo';
    }

    private function isActiveStatus(?TicketStatus $status): bool
    {
        return !$this->isClosedStatus($status)
            && !$this->isResolvedStatus($status)
            && $this->statusSlug($status) !== 'cancelado';
    }
}
