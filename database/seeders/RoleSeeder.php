<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrador', 'description' => 'Gestiona todo el sistema'],
            ['name' => 'Agente', 'description' => 'Atiende y da seguimiento a tickets'],
            ['name' => 'Usuario', 'description' => 'Registra y consulta sus tickets'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
        }
    }
}
