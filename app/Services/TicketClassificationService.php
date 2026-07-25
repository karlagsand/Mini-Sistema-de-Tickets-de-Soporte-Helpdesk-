<?php

namespace App\Services;

use App\Models\Priority;
use App\Models\Ticket;
use App\Models\User;

class TicketClassificationService
{
    public function applyStandardClassification(Ticket $ticket, ?User $requester = null): void
    {
        $requester ??= $ticket->creator;

        $classification = $this->classify(
            $requester,
            (string) ($ticket->request_type ?? 'solicitud'),
            $ticket->reported_impact,
            (int) ($ticket->category_id ?? 0)
        );

        $priority = Priority::where('level', $classification['priority_level'])->first();

        $ticket->impact = $classification['impact'];
        $ticket->urgency = $classification['urgency'];
        $ticket->priority_id = $priority?->id;
        $ticket->priority_reviewed_at = now();

        $this->applyResponseTimes($ticket, $priority?->level);
    }

    public function classify(?User $requester, string $requestType, ?string $reportedImpact, int $categoryId = 0): array
    {
        $impact = $this->standardImpact($reportedImpact);
        $urgency = $this->standardUrgency($requestType, $reportedImpact);
        $priorityLevel = $this->priorityLevel($impact, $urgency, $requester);

        return [
            'impact' => $impact,
            'urgency' => $urgency,
            'priority_level' => $priorityLevel,
        ];
    }

    public function applyResponseTimes(Ticket $ticket, ?int $priorityLevel): void
    {
        if (!$priorityLevel) {
            $ticket->first_response_due_at = null;
            $ticket->resolution_due_at = null;
            return;
        }

        $base = $ticket->opened_at ?? now();

        $hours = match ((int) $priorityLevel) {
            4 => ['response' => 1, 'resolution' => 4],
            3 => ['response' => 2, 'resolution' => 8],
            2 => ['response' => 4, 'resolution' => 24],
            1 => ['response' => 8, 'resolution' => 48],
            default => ['response' => 4, 'resolution' => 24],
        };

        $ticket->first_response_due_at = $base->copy()->addHours($hours['response']);
        $ticket->resolution_due_at = $base->copy()->addHours($hours['resolution']);
    }

    private function standardImpact(?string $reportedImpact): string
    {
        return match ($reportedImpact) {
            'sin_trabajar', 'varias_personas' => 'alto',
            'solo_mi_equipo' => 'medio',
            default => 'bajo',
        };
    }

    private function standardUrgency(string $requestType, ?string $reportedImpact): string
    {
        if ($reportedImpact === 'sin_trabajar') {
            return 'alta';
        }

        if ($requestType === 'incidente' && $reportedImpact === 'varias_personas') {
            return 'alta';
        }

        if ($requestType === 'incidente') {
            return 'media';
        }

        if (in_array($requestType, ['cambio', 'solicitud'], true) && $reportedImpact === 'varias_personas') {
            return 'media';
        }

        return 'baja';
    }

    private function priorityLevel(string $impact, string $urgency, ?User $requester): int
    {
        $impactScore = match ($impact) {
            'alto' => 3,
            'medio' => 2,
            default => 1,
        };

        $urgencyScore = match ($urgency) {
            'alta' => 3,
            'media' => 2,
            default => 1,
        };

        $attentionWeight = (int) ($requester?->attention_weight ?? User::attentionWeightFor($requester?->position_level ?? 'operativo'));

        $attentionBoost = match (true) {
            $attentionWeight >= 100 => 1,
            $attentionWeight >= 80 => 1,
            default => 0,
        };

        $score = $impactScore + $urgencyScore + $attentionBoost;

        return match (true) {
            $score >= 7 => 4,
            $score >= 5 => 3,
            $score >= 3 => 2,
            default => 1,
        };
    }
}
