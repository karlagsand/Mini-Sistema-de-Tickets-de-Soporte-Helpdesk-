<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Priority;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatus;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CleanHelpdeskTestingSeeder extends Seeder
{
    /**
     * Limpia tickets de prueba y deja usuarios/agentes listos para validar la asignación automática.
     * Usar solo en ambiente local o de pruebas.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        TicketStatusHistory::truncate();
        TicketComment::truncate();
        Ticket::truncate();

        Schema::enableForeignKeyConstraints();

        $adminRole = Role::updateOrCreate(
            ['name' => 'Administrador'],
            ['description' => 'Acceso total al sistema']
        );

        $agentRole = Role::updateOrCreate(
            ['name' => 'Agente'],
            ['description' => 'Atiende, clasifica y da seguimiento a tickets']
        );

        $userRole = Role::updateOrCreate(
            ['name' => 'Usuario'],
            ['description' => 'Crea y consulta sus propios tickets']
        );

        Category::updateOrCreate(
            ['name' => 'Red'],
            ['description' => 'Conectividad, internet y red interna', 'is_active' => true]
        );

        Category::updateOrCreate(
            ['name' => 'Equipo de cómputo'],
            ['description' => 'Computadoras, periféricos y hardware', 'is_active' => true]
        );

        Category::updateOrCreate(
            ['name' => 'Sistemas'],
            ['description' => 'Accesos, aplicaciones y sistemas internos', 'is_active' => true]
        );

        foreach ([
            ['name' => 'Baja', 'level' => 1, 'color' => 'green'],
            ['name' => 'Media', 'level' => 2, 'color' => 'yellow'],
            ['name' => 'Alta', 'level' => 3, 'color' => 'orange'],
            ['name' => 'Crítica', 'level' => 4, 'color' => 'red'],
        ] as $priority) {
            Priority::updateOrCreate(
                ['level' => $priority['level']],
                ['name' => $priority['name'], 'color' => $priority['color']]
            );
        }

        foreach ([
            ['name' => 'Recibida', 'slug' => 'nuevo', 'sort_order' => 1, 'is_closed' => false],
            ['name' => 'En revisión', 'slug' => 'en-revision', 'sort_order' => 2, 'is_closed' => false],
            ['name' => 'En atención', 'slug' => 'en-proceso', 'sort_order' => 3, 'is_closed' => false],
            ['name' => 'Esperando respuesta del solicitante', 'slug' => 'en-espera-usuario', 'sort_order' => 4, 'is_closed' => false],
            ['name' => 'En pausa por proveedor', 'slug' => 'en-espera-proveedor', 'sort_order' => 5, 'is_closed' => false],
            ['name' => 'Solución registrada', 'slug' => 'resuelto', 'sort_order' => 6, 'is_closed' => false],
            ['name' => 'Reabierta', 'slug' => 'reabierto', 'sort_order' => 7, 'is_closed' => false],
            ['name' => 'Finalizada', 'slug' => 'cerrado', 'sort_order' => 8, 'is_closed' => true],
            ['name' => 'Cancelada', 'slug' => 'cancelado', 'sort_order' => 9, 'is_closed' => true],
            ['name' => 'Asignado (anterior)', 'slug' => 'asignado', 'sort_order' => 99, 'is_closed' => false],
        ] as $status) {
            TicketStatus::updateOrCreate(
                ['slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'sort_order' => $status['sort_order'],
                    'is_closed' => $status['is_closed'],
                ]
            );
        }

        User::updateOrCreate(
            ['email' => 'admin@helpdesk.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin12345*'),
                'role_id' => $adminRole->id,
                'position_level' => 'operativo',
                'attention_weight' => User::attentionWeightFor('operativo'),
            ]
        );

        foreach ([1, 2, 3] as $number) {
            User::updateOrCreate(
                ['email' => "agente{$number}@helpdesk.com"],
                [
                    'name' => "Agente {$number}",
                    'password' => Hash::make('Demo12345*'),
                    'role_id' => $agentRole->id,
                    'position_level' => 'operativo',
                    'attention_weight' => User::attentionWeightFor('operativo'),
                ]
            );
        }

        foreach ([
            ['email' => 'director@helpdesk.com', 'name' => 'Director General', 'level' => 'director_general'],
            ['email' => 'subdirector@helpdesk.com', 'name' => 'Subdirector', 'level' => 'subdirector'],
            ['email' => 'gerente@helpdesk.com', 'name' => 'Gerente', 'level' => 'gerente'],
            ['email' => 'operativo@helpdesk.com', 'name' => 'Operativo', 'level' => 'operativo'],
        ] as $demoUser) {
            User::updateOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => Hash::make('Demo12345*'),
                    'role_id' => $userRole->id,
                    'position_level' => $demoUser['level'],
                    'attention_weight' => User::attentionWeightFor($demoUser['level']),
                ]
            );
        }
    }
}
