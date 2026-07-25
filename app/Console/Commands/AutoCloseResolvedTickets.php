<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\TicketStatusHistory;
use App\Services\AuditLogger;
use App\Services\InternalNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoCloseResolvedTickets extends Command
{
    protected $signature = 'tickets:auto-close-resolved {--hours=24 : Horas de espera después de registrar la solución}';

    protected $description = 'Finaliza automáticamente las solicitudes con solución registrada cuando el solicitante no confirma dentro del periodo definido.';

    public function handle(AuditLogger $auditLogger, InternalNotificationService $notificationService): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);

        $resolvedStatus = TicketStatus::where('slug', 'resuelto')->first();
        $closedStatus = TicketStatus::where('slug', 'cerrado')->first();

        if (!$resolvedStatus || !$closedStatus) {
            $this->error('No se encontraron los estados requeridos: resuelto y cerrado.');
            return self::FAILURE;
        }

        $tickets = Ticket::with(['status', 'creator', 'assignee'])
            ->where('status_id', $resolvedStatus->id)
            ->whereNull('closed_at')
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<=', $cutoff)
            ->get();

        $closed = 0;

        foreach ($tickets as $ticket) {
            DB::transaction(function () use ($ticket, $closedStatus, $auditLogger, $notificationService, $hours, &$closed) {
                $previousStatusId = $ticket->status_id;
                $closedAt = now();
                $actorId = $ticket->assigned_to ?: $ticket->created_by;

                $ticket->forceFill([
                    'status_id' => $closedStatus->id,
                    'closed_at' => $closedAt,
                ])->save();

                TicketStatusHistory::create([
                    'ticket_id' => $ticket->id,
                    'previous_status_id' => $previousStatusId,
                    'new_status_id' => $closedStatus->id,
                    'changed_by' => $actorId,
                    'changed_at' => $closedAt,
                    'notes' => "Solicitud finalizada automáticamente después de {$hours} horas sin confirmación del solicitante.",
                ]);

                $auditLogger->log(
                    'ticket.auto_closed',
                    'Solicitud finalizada automáticamente por falta de confirmación del solicitante.',
                    $ticket,
                    ['status_id' => $previousStatusId],
                    ['status_id' => $closedStatus->id, 'closed_at' => $closedAt->toDateTimeString()],
                    $actorId
                );

                $notificationService->createForUser(
                    $ticket->created_by,
                    $ticket,
                    'Solicitud finalizada automáticamente',
                    'La solicitud se finalizó automáticamente porque no se recibió confirmación dentro del tiempo establecido.',
                    'info'
                );

                if ($ticket->assigned_to) {
                    $notificationService->createForUser(
                        $ticket->assigned_to,
                        $ticket,
                        'Caso finalizado automáticamente',
                        'El caso fue finalizado automáticamente por falta de confirmación del solicitante.',
                        'info'
                    );
                }

                $closed++;
            });
        }

        $this->info("Solicitudes finalizadas automáticamente: {$closed}.");

        return self::SUCCESS;
    }
}
