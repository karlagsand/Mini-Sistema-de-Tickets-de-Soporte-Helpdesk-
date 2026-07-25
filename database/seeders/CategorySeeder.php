<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Accesos y permisos' => [
                'description' => 'Contraseñas, cuentas, permisos, altas, bajas o acceso a sistemas.',
                'aliases' => ['Accesos'],
            ],
            'Equipo de cómputo y periféricos' => [
                'description' => 'Computadora, laptop, monitor, teclado, mouse, impresora u otro equipo físico.',
                'aliases' => ['Hardware', 'Equipo de cómputo'],
            ],
            'Internet y red' => [
                'description' => 'Conexión a internet, Wi-Fi, red interna, VPN o conectividad.',
                'aliases' => ['Red'],
            ],
            'Sistemas y aplicaciones' => [
                'description' => 'Problemas o dudas sobre sistemas internos, aplicaciones o programas.',
                'aliases' => ['Software', 'Sistemas'],
            ],
            'Correo y herramientas de trabajo' => [
                'description' => 'Correo electrónico, calendario, paquetería de oficina o herramientas colaborativas.',
                'aliases' => [],
            ],
            'No estoy seguro' => [
                'description' => 'Usar cuando no sea claro qué categoría corresponde. Soporte la clasificará.',
                'aliases' => ['Otro', 'General'],
            ],
        ];

        DB::transaction(function () use ($categories) {
            $newCategories = [];

            foreach ($categories as $name => $data) {
                $newCategories[$name] = Category::updateOrCreate(
                    ['name' => $name],
                    [
                        'description' => $data['description'],
                        'is_active' => true,
                    ]
                );
            }

            foreach ($categories as $name => $data) {
                $target = $newCategories[$name];

                foreach ($data['aliases'] as $alias) {
                    $old = Category::where('name', $alias)->first();

                    if (!$old || $old->id === $target->id) {
                        continue;
                    }

                    Ticket::where('category_id', $old->id)->update([
                        'category_id' => $target->id,
                    ]);

                    $old->update([
                        'is_active' => false,
                    ]);
                }
            }
        });
    }
}
