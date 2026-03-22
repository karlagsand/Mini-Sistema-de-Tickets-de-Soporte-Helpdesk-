<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::insert([
            ['name' => 'Hardware', 'description' => 'Problemas físicos de equipo', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Software', 'description' => 'Problemas de programas o sistemas', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Red', 'description' => 'Conectividad e internet', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Accesos', 'description' => 'Credenciales, permisos y usuarios', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}