<?php

namespace Database\Seeders;

use App\Models\Priority;
use Illuminate\Database\Seeder;

class PrioritySeeder extends Seeder
{
    public function run(): void
    {
        $priorities = [
            ['name' => 'Baja', 'level' => 1, 'color' => 'gray'],
            ['name' => 'Media', 'level' => 2, 'color' => 'blue'],
            ['name' => 'Alta', 'level' => 3, 'color' => 'orange'],
            ['name' => 'Crítica', 'level' => 4, 'color' => 'red'],
        ];

        foreach ($priorities as $priority) {
            Priority::updateOrCreate(
                ['level' => $priority['level']],
                [
                    'name' => $priority['name'],
                    'color' => $priority['color'],
                ]
            );
        }
    }
}
