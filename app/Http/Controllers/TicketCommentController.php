<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\InternalNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketCommentController extends Controller
{
    public function store(Request $request, Ticket $ticket, AuditLogger $auditLogger, InternalNotificationService $notificationService): RedirectResponse
    {
        $request->validate([
            'comment' => ['required', 'string', 'max:5000'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if ($user->isUserRole() && $ticket->created_by !== $user->id) {
            abort(403, 'No autorizado para comentar esta solicitud.');
        }

        if ($user->isUserRole() && !$ticket->canRequesterAddFollowUp()) {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('error', 'Esta solicitud ya no admite mensajes de seguimiento. Si tiene una solución registrada y el problema continúa, usa la opción “Solicitar revisión adicional”.');
        }

        if ($user->isAgent()) {
            if (!is_null($ticket->assigned_to) && $ticket->assigned_to !== $user->id) {
                abort(403, 'No autorizado para comentar este ticket.');
            }
        }

        if (!$user->isAdmin() && !$user->isAgent() && $request->boolean('is_internal')) {
            abort(403, 'No autorizado para registrar notas internas.');
        }

        $isInternal = ($user->isAgent() || $user->isAdmin()) && $request->boolean('is_internal');

        DB::transaction(function () use ($request, $ticket, $user, $isInternal, $auditLogger, $notificationService) {
            TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'comment' => $request->comment,
                'is_internal' => $isInternal,
            ]);

            if (($user->isAgent() || $user->isAdmin()) && !$isInternal && is_null($ticket->first_responded_at)) {
                $ticket->first_responded_at = now();
                $ticket->save();
            }

            $auditLogger->log(
                $isInternal ? 'comment.internal_created' : 'comment.public_created',
                $isInternal ? 'Se registró una nota interna.' : 'Se registró una respuesta de seguimiento.',
                $ticket,
                null,
                ['is_internal' => $isInternal]
            );

            if (!$isInternal) {
                if ($user->isAgent() || $user->isAdmin()) {
                    $notificationService->createForUser(
                        $ticket->created_by,
                        $ticket,
                        'Nueva actualización en tu solicitud',
                        'Se agregó una respuesta al seguimiento de tu solicitud.',
                        'info'
                    );
                } elseif ($ticket->assigned_to) {
                    $notificationService->createForUser(
                        $ticket->assigned_to,
                        $ticket,
                        'Respuesta del solicitante',
                        'El solicitante agregó información al caso.',
                        'info'
                    );
                } else {
                    $notificationService->notifyAdmins(
                        $ticket,
                        'Respuesta en caso sin responsable',
                        'El solicitante agregó información en una solicitud sin responsable asignado.',
                        'warning'
                    );
                }
            }
        });

        $message = 'Mensaje enviado correctamente.';

        if ($user->isAgent() || $user->isAdmin()) {
            $message = $isInternal
                ? 'Nota interna guardada correctamente.'
                : 'Respuesta enviada al solicitante correctamente.';
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', $message);
    }
}
