<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoHelpdeskUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('Helpdesk123');

        $users = [
            [
                'name' => 'Administrador General',
                'email' => 'admin@helpdesk.com',
                'role_id' => 1,
                'position_level' => 'operativo',
                'attention_weight' => 20,
            ],
            [
                'name' => 'Agente 1',
                'email' => 'agente1@helpdesk.com',
                'role_id' => 2,
                'position_level' => 'operativo',
                'attention_weight' => 20,
            ],
            [
                'name' => 'Agente 2',
                'email' => 'agente2@helpdesk.com',
                'role_id' => 2,
                'position_level' => 'operativo',
                'attention_weight' => 20,
            ],
            [
                'name' => 'Agente 3',
                'email' => 'agente3@helpdesk.com',
                'role_id' => 2,
                'position_level' => 'operativo',
                'attention_weight' => 20,
            ],
            [
                'name' => 'Director General',
                'email' => 'director@helpdesk.com',
                'role_id' => 3,
                'position_level' => 'director_general',
                'attention_weight' => 100,
            ],
            [
                'name' => 'Subdirector',
                'email' => 'subdirector@helpdesk.com',
                'role_id' => 3,
                'position_level' => 'subdirector',
                'attention_weight' => 80,
            ],
            [
                'name' => 'Gerente',
                'email' => 'gerente@helpdesk.com',
                'role_id' => 3,
                'position_level' => 'gerente',
                'attention_weight' => 60,
            ],
            [
                'name' => 'Operativo',
                'email' => 'operativo@helpdesk.com',
                'role_id' => 3,
                'position_level' => 'operativo',
                'attention_weight' => 20,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    'role_id' => $user['role_id'],
                    'position_level' => $user['position_level'],
                    'attention_weight' => $user['attention_weight'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'email_verified_at' => now(),
                    'password' => $password,
                    'remember_token' => Str::random(10),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}