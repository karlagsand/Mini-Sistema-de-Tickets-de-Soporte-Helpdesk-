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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestingHelpdeskSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'Administrador')->first();
        $agentRole = Role::where('name', 'Agente')->first();
        $userRole = Role::where('name', 'Usuario')->first();

        if (!$adminRole || !$agentRole || !$userRole) {
            $this->command?->warn('Primero ejecuta RoleSeeder o DatabaseSeeder para crear los roles base.');
            return;
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@helpdesk.com'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('Admin12345*'),
                'role_id' => $adminRole->id,
                'position_level' => 'director_general',
                'attention_weight' => User::attentionWeightFor('director_general'),
                'email_verified_at' => now(),
            ]
        );

        $agents = collect([
            ['name' => 'Agente Soporte 1', 'email' => 'agente1@helpdesk.com'],
            ['name' => 'Agente Soporte 2', 'email' => 'agente2@helpdesk.com'],
            ['name' => 'Agente Soporte 3', 'email' => 'agente3@helpdesk.com'],
        ])->map(function (array $agent) use ($agentRole) {
            return User::updateOrCreate(
                ['email' => $agent['email']],
                [
                    'name' => $agent['name'],
                    'password' => Hash::make('Demo12345*'),
                    'role_id' => $agentRole->id,
                    'position_level' => 'operativo',
                    'attention_weight' => User::attentionWeightFor('operativo'),
                    'email_verified_at' => now(),
                ]
            );
        });

        $requesters = collect([
            ['name' => 'Director General', 'email' => 'director@helpdesk.com', 'level' => 'director_general'],
            ['name' => 'Subdirector', 'email' => 'subdirector@helpdesk.com', 'level' => 'subdirector'],
            ['name' => 'Gerente Comercial', 'email' => 'gerente@helpdesk.com', 'level' => 'gerente'],
            ['name' => 'Usuario Operativo', 'email' => 'operativo@helpdesk.com', 'level' => 'operativo'],
        ])->map(function (array $user) use ($userRole) {
            return User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('Demo12345*'),
                    'role_id' => $userRole->id,
                    'position_level' => $user['level'],
                    'attention_weight' => User::attentionWeightFor($user['level']),
                    'email_verified_at' => now(),
                ]
            );
        });

        $demoTicketIds = Ticket::where('folio', 'like', 'DEMO-%')->pluck('id');

        if ($demoTicketIds->isNotEmpty()) {
            DB::table('ticket_comments')->whereIn('ticket_id', $demoTicketIds)->delete();
            DB::table('ticket_status_histories')->whereIn('ticket_id', $demoTicketIds)->delete();
            Ticket::whereIn('id', $demoTicketIds)->delete();
        }

        $categories = Category::all();
        $priorities = Priority::all();

        if ($categories->isEmpty() || $priorities->isEmpty()) {
            $this->command?->warn('No hay categorías o prioridades. Primero ejecuta los seeders base.');
            return;
        }

        $statusNuevo = TicketStatus::where('slug', 'nuevo')->first();
        $statusEnRevision = TicketStatus::where('slug', 'en-revision')->first() ?? $statusNuevo;
        $statusAsignado = TicketStatus::where('slug', 'asignado')->first() ?? $statusEnRevision;
        $statusEnProceso = TicketStatus::where('slug', 'en-proceso')->first() ?? $statusAsignado;
        $statusPool = [$statusNuevo, $statusEnRevision, $statusAsignado, $statusEnProceso];

        $samples = [
            ['subject' => 'Falla de internet en área comercial', 'impact' => 'varias_personas', 'type' => 'incidente'],
            ['subject' => 'Solicitud de acceso a sistema interno', 'impact' => 'solo_mi_equipo', 'type' => 'solicitud'],
            ['subject' => 'Equipo sin conexión a impresora', 'impact' => 'solo_mi_equipo', 'type' => 'incidente'],
            ['subject' => 'Consulta sobre cuenta de correo', 'impact' => 'duda_general', 'type' => 'consulta'],
            ['subject' => 'Cambio de permisos de usuario', 'impact' => 'sin_trabajar', 'type' => 'cambio'],
            ['subject' => 'Intermitencia en red inalámbrica', 'impact' => 'varias_personas', 'type' => 'incidente'],
            ['subject' => 'Alta de usuario para nuevo colaborador', 'impact' => 'duda_general', 'type' => 'solicitud'],
            ['subject' => 'Actualización de software requerida', 'impact' => 'solo_mi_equipo', 'type' => 'solicitud'],
        ];

        foreach ($samples as $index => $sample) {
            $openedAt = now()->subHours(8 - $index);
            $status = $statusPool[$index % count($statusPool)];
            $creator = $requesters[$index % $requesters->count()];
            $assignee = $index < 2 ? null : $agents[$index % $agents->count()];
            $priority = $index < 2 ? null : $priorities->sortByDesc('level')->values()[$index % $priorities->count()];

            $ticket = Ticket::create([
                'folio' => 'DEMO-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'subject' => $sample['subject'],
                'description' => 'Ticket de prueba para validar el flujo profesional de mesa de ayuda.',
                'request_type' => $sample['type'],
                'reported_impact' => $sample['impact'],
                'category_id' => $categories->random()->id,
                'priority_id' => $priority?->id,
                'impact' => $priority ? ['alto', 'medio', 'bajo'][$index % 3] : null,
                'urgency' => $priority ? ['alta', 'media', 'baja'][$index % 3] : null,
                'priority_reviewed_at' => $priority ? $openedAt->copy()->addMinutes(30) : null,
                'first_response_due_at' => $priority ? $openedAt->copy()->addHours(2) : null,
                'resolution_due_at' => $priority ? $openedAt->copy()->addHours(24) : null,
                'status_id' => $status?->id,
                'created_by' => $creator->id,
                'assigned_to' => $assignee?->id,
                'opened_at' => $openedAt,
                'created_at' => $openedAt,
                'updated_at' => $openedAt,
            ]);

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'previous_status_id' => null,
                'new_status_id' => $status?->id,
                'changed_by' => $admin->id,
                'notes' => 'Ticket de demostración creado para pruebas',
                'changed_at' => $openedAt,
            ]);
        }

        $this->command?->info('Seeder de pruebas aplicado: 3 agentes, 4 solicitantes y 8 tickets DEMO.');
    }
}
