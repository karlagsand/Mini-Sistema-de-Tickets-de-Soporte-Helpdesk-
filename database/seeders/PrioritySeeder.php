<?php

namespace Database\Seeders;

use App\Models\Priority;
use Illuminate\Database\Seeder;

class PrioritySeeder extends Seeder
{
    public function run(): void
    {
        Priority::insert([
            ['name' => 'Baja', 'level' => 1, 'color' => 'gray', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Media', 'level' => 2, 'color' => 'blue', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Alta', 'level' => 3, 'color' => 'orange', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Crítica', 'level' => 4, 'color' => 'red', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}