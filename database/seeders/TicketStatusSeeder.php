<?php

namespace Database\Seeders;

use App\Models\TicketStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Recibida', 'slug' => 'nuevo', 'sort_order' => 1, 'is_closed' => false],
            ['name' => 'En revisión', 'slug' => 'en-revision', 'sort_order' => 2, 'is_closed' => false],
            ['name' => 'En atención', 'slug' => 'en-proceso', 'sort_order' => 3, 'is_closed' => false],
            ['name' => 'Esperando respuesta del solicitante', 'slug' => 'en-espera-usuario', 'sort_order' => 4, 'is_closed' => false],
            ['name' => 'En pausa por proveedor', 'slug' => 'en-espera-proveedor', 'sort_order' => 5, 'is_closed' => false],
            ['name' => 'Solución registrada', 'slug' => 'resuelto', 'sort_order' => 6, 'is_closed' => false],
            ['name' => 'Reabierta', 'slug' => 'reabierto', 'sort_order' => 7, 'is_closed' => false],
            ['name' => 'Finalizada', 'slug' => 'cerrado', 'sort_order' => 8, 'is_closed' => true],
            ['name' => 'Cancelada', 'slug' => 'cancelado', 'sort_order' => 9, 'is_closed' => true],

            // Estado anterior conservado por compatibilidad con instalaciones que ya lo tenían.
            // No se muestra en los formularios nuevos porque la asignación ya se controla con el campo Responsable.
            ['name' => 'Asignado (anterior)', 'slug' => 'asignado', 'sort_order' => 99, 'is_closed' => false],
        ];

        foreach ($statuses as $status) {
            TicketStatus::updateOrCreate(
                ['slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'sort_order' => $status['sort_order'],
                    'is_closed' => $status['is_closed'],
                ]
            );
        }

        $legacyAssigned = TicketStatus::where('slug', 'asignado')->first();
        $inReview = TicketStatus::where('slug', 'en-revision')->first();

        if ($legacyAssigned && $inReview) {
            DB::table('tickets')
                ->where('status_id', $legacyAssigned->id)
                ->update(['status_id' => $inReview->id]);
        }
    }
}
