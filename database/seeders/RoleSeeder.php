<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::insert([
            ['name' => 'Administrador', 'description' => 'Gestiona todo el sistema', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Agente', 'description' => 'Atiende y da seguimiento a tickets', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Usuario', 'description' => 'Registra y consulta sus tickets', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}