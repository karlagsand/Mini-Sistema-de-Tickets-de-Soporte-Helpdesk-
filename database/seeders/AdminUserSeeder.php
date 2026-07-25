<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'Administrador')->first();

        User::updateOrCreate(
            ['email' => 'admin@helpdesk.com'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('Admin12345*'),
                'role_id' => $adminRole?->id,
                'position_level' => 'director_general',
                'attention_weight' => User::attentionWeightFor('director_general'),
                'email_verified_at' => now(),
            ]
        );
    }
}
