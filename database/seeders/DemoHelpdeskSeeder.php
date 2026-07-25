<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Priority;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoHelpdeskSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'Administrador')->first();
        $agentRole = Role::where('name', 'Agente')->first();
        $userRole = Role::where('name', 'Usuario')->first();

        $admin = User::updateOrCreate(
            ['email' => 'admin@helpdesk.com'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('Admin12345*'),
                'role_id' => $adminRole?->id,
                'position_level' => 'director_general',
                'attention_weight' => User::attentionWeightFor('director_general'),
                'email_verified_at' => now(),
            ]
        );

        $agent1 = User::updateOrCreate(
            ['email' => 'agente1@helpdesk.com'],
            [
                'name' => 'Agente Uno',
                'password' => Hash::make('Demo12345*'),
                'role_id' => $agentRole?->id,
                'position_level' => 'operativo',
                'attention_weight' => User::attentionWeightFor('operativo'),
                'email_verified_at' => now(),
            ]
        );

        $agent2 = User::updateOrCreate(
            ['email' => 'agente2@helpdesk.com'],
            [
                'name' => 'Agente Dos',
                'password' => Hash::make('Demo12345*'),
                'role_id' => $agentRole?->id,
                'position_level' => 'operativo',
                'attention_weight' => User::attentionWeightFor('operativo'),
                'email_verified_at' => now(),
            ]
        );

        $user1 = User::updateOrCreate(
            ['email' => 'usuario1@helpdesk.com'],
            [
                'name' => 'Usuario Uno',
                'password' => Hash::make('Demo12345*'),
                'role_id' => $userRole?->id,
                'position_level' => 'gerente',
                'attention_weight' => User::attentionWeightFor('gerente'),
                'email_verified_at' => now(),
            ]
        );

        $user2 = User::updateOrCreate(
            ['email' => 'usuario2@helpdesk.com'],
            [
                'name' => 'Usuario Dos',
                'password' => Hash::make('Demo12345*'),
                'role_id' => $userRole?->id,
                'position_level' => 'operativo',
                'attention_weight' => User::attentionWeightFor('operativo'),
                'email_verified_at' => now(),
            ]
        );

        $categories = Category::all();
        $priorities = Priority::all();

        if ($categories->isEmpty() || $priorities->isEmpty()) {
            return;
        }

        $statusNuevo = TicketStatus::where('slug', 'nuevo')->first();
        $statusEnProceso = TicketStatus::where('slug', 'en-proceso')->first();
        $statusResuelto = TicketStatus::where('slug', 'resuelto')->first();
        $statusCerrado = TicketStatus::where('slug', 'cerrado')->first();

        $statusPool = [
            $statusNuevo,
            $statusEnProceso,
            $statusResuelto,
            $statusCerrado,
        ];

        $creators = [$user1, $user2];
        $agents = [null, $agent1->id, $agent2->id];

        for ($i = 1; $i <= 30; $i++) {
            $openedAt = now()->subDays(rand(1, 20))->subHours(rand(1, 12));
            $selectedStatus = $statusPool[array_rand($statusPool)];
            $selectedCreator = $creators[array_rand($creators)];
            $selectedAgentId = $agents[array_rand($agents)];

            $resolvedAt = null;
            $closedAt = null;

            if ($selectedStatus && in_array($selectedStatus->slug, ['resuelto', 'cerrado'], true)) {
                $resolvedAt = (clone $openedAt)->addHours(rand(2, 72));
            }

            if ($selectedStatus && $selectedStatus->slug === 'cerrado') {
                $baseResolved = $resolvedAt ?? (clone $openedAt)->addHours(rand(2, 72));
                $closedAt = (clone $baseResolved)->addHours(rand(1, 24));
            }

            $ticket = Ticket::updateOrCreate(
                ['folio' => 'DEMO-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT)],
                [
                    'subject' => 'Ticket de demostración ' . $i,
                    'description' => 'Descripción del ticket de demostración número ' . $i . '. Sirve para probar el flujo completo del sistema de mesa de ayuda.',
                    'request_type' => ['incidente', 'solicitud', 'consulta', 'cambio'][array_rand(['incidente', 'solicitud', 'consulta', 'cambio'])],
                    'reported_impact' => ['sin_trabajar', 'varias_personas', 'solo_mi_equipo', 'duda_general'][array_rand(['sin_trabajar', 'varias_personas', 'solo_mi_equipo', 'duda_general'])],
                    'category_id' => $categories->random()->id,
                    'priority_id' => $priorities->random()->id,
                    'impact' => ['alto', 'medio', 'bajo'][array_rand(['alto', 'medio', 'bajo'])],
                    'urgency' => ['alta', 'media', 'baja'][array_rand(['alta', 'media', 'baja'])],
                    'priority_reviewed_at' => $openedAt->copy()->addMinutes(rand(10, 90)),
                    'first_response_due_at' => $openedAt->copy()->addHours(rand(1, 8)),
                    'resolution_due_at' => $openedAt->copy()->addHours(rand(8, 48)),
                    'status_id' => $selectedStatus?->id,
                    'created_by' => $selectedCreator->id,
                    'assigned_to' => $selectedAgentId,
                    'opened_at' => $openedAt,
                    'resolved_at' => $resolvedAt,
                    'closed_at' => $closedAt,
                    'created_at' => $openedAt,
                    'updated_at' => $closedAt ?? $resolvedAt ?? $openedAt,
                ]
            );

            TicketStatusHistory::updateOrCreate(
                [
                    'ticket_id' => $ticket->id,
                    'changed_at' => $openedAt,
                ],
                [
                    'previous_status_id' => null,
                    'new_status_id' => $selectedStatus?->id,
                    'changed_by' => $admin->id,
                    'notes' => 'Ticket de demostración creado automáticamente',
                ]
            );
        }
    }
}
