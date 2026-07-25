<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoDashboardTicketsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->deletePreviousDemoTickets();

            $statuses = $this->ensureStatuses();
            $priorities = $this->ensurePriorities();
            $categories = $this->ensureCategories();

            $requestUsers = User::query()
                ->whereIn('email', [
                    'director@helpdesk.com',
                    'subdirector@helpdesk.com',
                    'gerente@helpdesk.com',
                    'operativo@helpdesk.com',
                ])
                ->get()
                ->values();

            $agents = User::query()
                ->whereIn('email', [
                    'agente1@helpdesk.com',
                    'agente2@helpdesk.com',
                    'agente3@helpdesk.com',
                ])
                ->get()
                ->values();

            if ($requestUsers->isEmpty() || $agents->isEmpty()) {
                throw new \RuntimeException('Primero ejecuta el seeder de usuarios demo para crear solicitantes y agentes.');
            }

            $requestTypes = array_keys(Ticket::REQUEST_TYPES ?? []);
            if (empty($requestTypes)) {
                $requestTypes = ['incidente', 'solicitud', 'consulta', 'cambio'];
            }

            $reportedImpacts = array_keys(Ticket::REPORTED_IMPACT_OPTIONS ?? []);
            if (empty($reportedImpacts)) {
                $reportedImpacts = ['bajo', 'medio', 'alto', 'critico'];
            }

            $subjects = [
                'No puedo acceder al sistema institucional',
                'Equipo de cómputo con lentitud',
                'Error al imprimir documentos',
                'Solicitud de instalación de software',
                'Problema con conexión a internet',
                'Restablecimiento de contraseña',
                'Correo institucional no sincroniza',
                'Actualización de permisos de usuario',
                'Falla en acceso a carpeta compartida',
                'Equipo no enciende correctamente',
                'Problema con videoconferencia',
                'Solicitud de revisión de equipo',
                'Error en carga de archivos',
                'Configuración de impresora',
                'Falla intermitente en red',
            ];

            $descriptions = [
                'El usuario reporta que el problema afecta sus actividades diarias y requiere seguimiento del área de soporte.',
                'Se presenta una incidencia técnica que impide continuar con el trabajo de manera normal.',
                'El solicitante requiere apoyo para validar configuración, permisos o funcionamiento del servicio.',
                'Se solicita revisión por comportamiento inesperado en el equipo o sistema utilizado.',
                'El caso requiere diagnóstico del agente y confirmación posterior por parte del solicitante.',
            ];

            $scenarios = [
                // Activos normales
                ['status' => 'nuevo', 'priority' => 'media', 'days' => 0, 'agent' => null, 'response' => false, 'overdue' => false, 'dueSoon' => false],
                ['status' => 'en-revision', 'priority' => 'alta', 'days' => 1, 'agent' => 0, 'response' => true, 'overdue' => false, 'dueSoon' => false],
                ['status' => 'en-proceso', 'priority' => 'media', 'days' => 2, 'agent' => 1, 'response' => true, 'overdue' => false, 'dueSoon' => false],
                ['status' => 'en-espera-usuario', 'priority' => 'baja', 'days' => 3, 'agent' => 2, 'response' => true, 'overdue' => false, 'dueSoon' => false],
                ['status' => 'en-espera-proveedor', 'priority' => 'media', 'days' => 4, 'agent' => 0, 'response' => true, 'overdue' => false, 'dueSoon' => false],

                // Por vencer
                ['status' => 'en-proceso', 'priority' => 'alta', 'days' => 2, 'agent' => 1, 'response' => true, 'overdue' => false, 'dueSoon' => true],
                ['status' => 'en-revision', 'priority' => 'urgente', 'days' => 1, 'agent' => 2, 'response' => true, 'overdue' => false, 'dueSoon' => true],

                // Vencidos
                ['status' => 'nuevo', 'priority' => 'alta', 'days' => 5, 'agent' => null, 'response' => false, 'overdue' => true, 'dueSoon' => false],
                ['status' => 'en-proceso', 'priority' => 'urgente', 'days' => 6, 'agent' => 0, 'response' => true, 'overdue' => true, 'dueSoon' => false],
                ['status' => 'reabierto', 'priority' => 'alta', 'days' => 8, 'agent' => 1, 'response' => true, 'overdue' => true, 'dueSoon' => false],

                // Solución registrada, pendiente de confirmar
                ['status' => 'resuelto', 'priority' => 'media', 'days' => 4, 'agent' => 2, 'response' => true, 'resolved' => true, 'closed' => false],
                ['status' => 'resuelto', 'priority' => 'alta', 'days' => 7, 'agent' => 0, 'response' => true, 'resolved' => true, 'closed' => false],

                // Finalizados con satisfacción
                ['status' => 'cerrado', 'priority' => 'baja', 'days' => 10, 'agent' => 0, 'response' => true, 'resolved' => true, 'closed' => true, 'rating' => 5],
                ['status' => 'cerrado', 'priority' => 'media', 'days' => 12, 'agent' => 1, 'response' => true, 'resolved' => true, 'closed' => true, 'rating' => 4],
                ['status' => 'cerrado', 'priority' => 'alta', 'days' => 15, 'agent' => 2, 'response' => true, 'resolved' => true, 'closed' => true, 'rating' => 5],
                ['status' => 'cerrado', 'priority' => 'urgente', 'days' => 18, 'agent' => 0, 'response' => true, 'resolved' => true, 'closed' => true, 'rating' => 3],
                ['status' => 'cerrado', 'priority' => 'media', 'days' => 20, 'agent' => 1, 'response' => true, 'resolved' => true, 'closed' => true, 'rating' => 4],

                // Cancelados
                ['status' => 'cancelado', 'priority' => null, 'days' => 9, 'agent' => null, 'response' => false, 'closed' => true],
                ['status' => 'cancelado', 'priority' => 'baja', 'days' => 16, 'agent' => 2, 'response' => true, 'closed' => true],
            ];

            $totalTickets = 45;

            for ($i = 1; $i <= $totalTickets; $i++) {
                $scenario = $scenarios[($i - 1) % count($scenarios)];

                $creator = $requestUsers[($i - 1) % $requestUsers->count()];
                $agentIndex = $scenario['agent'];

                $assignee = is_null($agentIndex)
                    ? null
                    : $agents[$agentIndex % $agents->count()];

                $status = $statuses[$scenario['status']] ?? $statuses['nuevo'];
                $priority = $scenario['priority']
                    ? ($priorities[$scenario['priority']] ?? null)
                    : null;

                $category = $categories[($i - 1) % count($categories)];
                $createdAt = now()->subDays((int) $scenario['days'])->subHours($i % 6);
                $openedAt = $createdAt->copy();

                $firstResponseDueAt = $createdAt->copy()->addHours($this->responseHoursForPriority($scenario['priority']));
                $resolutionDueAt = $createdAt->copy()->addHours($this->resolutionHoursForPriority($scenario['priority']));

                if (!empty($scenario['overdue'])) {
                    $firstResponseDueAt = now()->subHours(12 + ($i % 5));
                    $resolutionDueAt = now()->subHours(6 + ($i % 8));
                }

                if (!empty($scenario['dueSoon'])) {
                    $resolutionDueAt = now()->addHours(2 + ($i % 5));
                }

                $firstRespondedAt = !empty($scenario['response'])
                    ? $createdAt->copy()->addHours(rand(1, 6))
                    : null;

                $resolvedAt = !empty($scenario['resolved'])
                    ? $createdAt->copy()->addDays(rand(1, 3))->addHours(rand(1, 8))
                    : null;

                $closedAt = !empty($scenario['closed'])
                    ? ($resolvedAt ? $resolvedAt->copy()->addHours(rand(2, 20)) : $createdAt->copy()->addDays(rand(1, 3)))
                    : null;

                $rating = $scenario['rating'] ?? null;

                $ticketId = $this->insertTicket([
                    'folio' => 'HD-DEMO-' . now()->format('Ymd') . '-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    'subject' => $subjects[($i - 1) % count($subjects)],
                    'description' => $descriptions[($i - 1) % count($descriptions)],
                    'request_type' => $requestTypes[($i - 1) % count($requestTypes)],
                    'reported_impact' => $reportedImpacts[($i - 1) % count($reportedImpacts)],
                    'category_id' => $category,
                    'priority_id' => $priority,
                    'status_id' => $status,
                    'created_by' => $creator->id,
                    'assigned_to' => $assignee?->id,
                    'opened_at' => $openedAt,
                    'first_response_due_at' => $firstResponseDueAt,
                    'resolution_due_at' => $resolutionDueAt,
                    'first_responded_at' => $firstRespondedAt,
                    'resolved_at' => $resolvedAt,
                    'closed_at' => $closedAt,
                    'satisfaction_rating' => $rating,
                    'satisfaction_comment' => $rating
                        ? $this->satisfactionComment($rating)
                        : null,
                    'satisfaction_submitted_at' => $rating ? $closedAt : null,
                    'impact' => $this->impactForPriority($scenario['priority']),
                    'urgency' => $this->urgencyForPriority($scenario['priority']),
                    'created_at' => $createdAt,
                    'updated_at' => $closedAt ?? $resolvedAt ?? now()->subHours($i % 24),
                ]);

                $this->insertHistory($ticketId, null, $status, $creator->id, $createdAt, 'Solicitud demo registrada para pruebas del dashboard.');

                if ($firstRespondedAt && $assignee) {
                    $this->insertComment(
                        $ticketId,
                        $assignee->id,
                        'Se revisa la solicitud y se inicia seguimiento por parte de mesa de ayuda.',
                        false,
                        $firstRespondedAt
                    );

                    $this->insertComment(
                        $ticketId,
                        $assignee->id,
                        'Nota interna demo: se valida diagnóstico, prioridad y evidencia disponible.',
                        true,
                        $firstRespondedAt->copy()->addMinutes(20)
                    );
                }

                if ($resolvedAt && $assignee) {
                    $this->insertComment(
                        $ticketId,
                        $assignee->id,
                        'Se registra solución para revisión del solicitante.',
                        false,
                        $resolvedAt
                    );
                }

                if ($closedAt) {
                    $this->insertHistory($ticketId, $status, $status, $creator->id, $closedAt, 'Solicitud demo finalizada o cerrada para estadísticas.');
                }
            }
        });
    }

    private function deletePreviousDemoTickets(): void
    {
        $ids = DB::table('tickets')
            ->where('folio', 'like', 'HD-DEMO-%')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        foreach ([
            'ticket_comments',
            'ticket_attachments',
            'ticket_status_histories',
            'audit_logs',
            'internal_notifications',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->whereIn('ticket_id', $ids)->delete();
            }
        }

        DB::table('tickets')->whereIn('id', $ids)->delete();
    }

    private function ensureStatuses(): array
    {
        $items = [
            'nuevo' => ['Recibida', false, 1],
            'en-revision' => ['En revisión', false, 2],
            'en-proceso' => ['En atención', false, 3],
            'en-espera-usuario' => ['Esperando respuesta del solicitante', false, 4],
            'en-espera-proveedor' => ['En pausa por proveedor', false, 5],
            'resuelto' => ['Solución registrada', false, 6],
            'reabierto' => ['Reabierta', false, 7],
            'cerrado' => ['Finalizada', true, 8],
            'cancelado' => ['Cancelada', true, 9],
        ];

        $result = [];

        foreach ($items as $slug => [$name, $isClosed, $sortOrder]) {
            $result[$slug] = $this->upsertSimple('ticket_statuses', [
                'name' => $name,
                'slug' => $slug,
                'is_closed' => $isClosed,
                'sort_order' => $sortOrder,
            ]);
        }

        return $result;
    }

   private function ensurePriorities(): array
    {
        $items = [
            'baja' => ['Baja', 1],
            'media' => ['Media', 2],
            'alta' => ['Alta', 3],
            'urgente' => ['Urgente', 4],
        ];

        $result = [];

        foreach ($items as $slug => [$name, $level]) {
            $existing = DB::table('priorities')
                ->where('level', $level)
                ->first();

            if ($existing) {
                $result[$slug] = (int) $existing->id;
                continue;
            }

            $payload = [
                'name' => $name,
                'level' => $level,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('priorities', 'slug')) {
                $payload['slug'] = $slug;
            }

            $result[$slug] = (int) DB::table('priorities')->insertGetId(
                $this->onlyExistingColumns('priorities', $payload)
            );
        }

        return $result;
    }

    private function ensureCategories(): array
    {
        $items = [
            'Accesos y contraseñas',
            'Equipo de cómputo',
            'Red e internet',
            'Correo institucional',
            'Impresoras',
            'Software y sistemas',
            'Archivos compartidos',
        ];

        $result = [];

        foreach ($items as $name) {
            $result[] = $this->upsertSimple('categories', [
                'name' => $name,
                'slug' => Str::slug($name),
                'is_active' => true,
            ]);
        }

        return $result;
    }

    private function upsertSimple(string $table, array $data): int
    {
        $lookup = [];

        if (Schema::hasColumn($table, 'slug') && isset($data['slug'])) {
            $lookup['slug'] = $data['slug'];
        } else {
            $lookup['name'] = $data['name'];
        }

        $existing = DB::table($table)->where($lookup)->first();

        $payload = $this->onlyExistingColumns($table, $data + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($existing) {
            DB::table($table)->where('id', $existing->id)->update($payload);
            return (int) $existing->id;
        }

        return (int) DB::table($table)->insertGetId($payload);
    }

    private function insertTicket(array $data): int
    {
        return (int) DB::table('tickets')->insertGetId(
            $this->onlyExistingColumns('tickets', $data)
        );
    }

    private function insertHistory(int $ticketId, ?int $previousStatusId, int $newStatusId, int $changedBy, $changedAt, string $notes): void
    {
        if (!Schema::hasTable('ticket_status_histories')) {
            return;
        }

        DB::table('ticket_status_histories')->insert(
            $this->onlyExistingColumns('ticket_status_histories', [
                'ticket_id' => $ticketId,
                'previous_status_id' => $previousStatusId,
                'new_status_id' => $newStatusId,
                'changed_by' => $changedBy,
                'changed_at' => $changedAt,
                'notes' => $notes,
                'created_at' => $changedAt,
                'updated_at' => $changedAt,
            ])
        );
    }

    private function insertComment(int $ticketId, int $userId, string $comment, bool $isInternal, $createdAt): void
    {
        if (!Schema::hasTable('ticket_comments')) {
            return;
        }

        DB::table('ticket_comments')->insert(
            $this->onlyExistingColumns('ticket_comments', [
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'comment' => $comment,
                'is_internal' => $isInternal,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])
        );
    }

    private function onlyExistingColumns(string $table, array $data): array
    {
        return collect($data)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->all();
    }

    private function responseHoursForPriority(?string $priority): int
    {
        return match ($priority) {
            'urgente' => 2,
            'alta' => 4,
            'media' => 8,
            'baja' => 16,
            default => 12,
        };
    }

    private function resolutionHoursForPriority(?string $priority): int
    {
        return match ($priority) {
            'urgente' => 8,
            'alta' => 16,
            'media' => 32,
            'baja' => 48,
            default => 36,
        };
    }

    private function impactForPriority(?string $priority): string
    {
        return match ($priority) {
            'urgente' => 'critico',
            'alta' => 'alto',
            'media' => 'medio',
            'baja' => 'bajo',
            default => 'medio',
        };
    }

    private function urgencyForPriority(?string $priority): string
    {
        return match ($priority) {
            'urgente' => 'critica',
            'alta' => 'alta',
            'media' => 'media',
            'baja' => 'baja',
            default => 'media',
        };
    }

    private function satisfactionComment(int $rating): string
    {
        return match ($rating) {
            5 => 'La atención fue clara y el problema quedó resuelto correctamente.',
            4 => 'La atención fue buena, solo hubo detalles menores en el seguimiento.',
            3 => 'La atención fue aceptable, pero podría mejorar el tiempo de respuesta.',
            2 => 'La atención no fue suficiente y se requiere revisar el proceso.',
            default => 'La experiencia no fue satisfactoria.',
        };
    }
}