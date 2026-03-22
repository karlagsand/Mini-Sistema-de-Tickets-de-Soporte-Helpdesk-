<?php

namespace Database\Seeders;

use App\Models\TicketStatus;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    public function run(): void
    {
        TicketStatus::insert([
            ['name' => 'Nuevo', 'slug' => 'nuevo', 'sort_order' => 1, 'is_closed' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'En proceso', 'slug' => 'en-proceso', 'sort_order' => 2, 'is_closed' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Resuelto', 'slug' => 'resuelto', 'sort_order' => 3, 'is_closed' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cerrado', 'slug' => 'cerrado', 'sort_order' => 4, 'is_closed' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}