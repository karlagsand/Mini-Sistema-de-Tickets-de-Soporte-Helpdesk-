<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Services\TicketClassificationService;
use Illuminate\Database\Seeder;

class StandardizeTicketClassificationSeeder extends Seeder
{
    public function run(): void
    {
        $classifier = app(TicketClassificationService::class);

        Ticket::with('creator')
            ->where(function ($query) {
                $query->whereNull('priority_id')
                    ->orWhereNull('impact')
                    ->orWhereNull('urgency')
                    ->orWhereNull('first_response_due_at')
                    ->orWhereNull('resolution_due_at');
            })
            ->orderBy('id')
            ->chunkById(100, function ($tickets) use ($classifier) {
                foreach ($tickets as $ticket) {
                    $classifier->applyStandardClassification($ticket, $ticket->creator);
                    $ticket->save();
                }
            });
    }
}
