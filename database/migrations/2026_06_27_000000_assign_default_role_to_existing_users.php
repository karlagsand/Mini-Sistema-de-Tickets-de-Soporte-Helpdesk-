<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $userRoleId = DB::table('roles')
            ->where('name', 'Usuario')
            ->value('id');

        if (!$userRoleId) {
            return;
        }

        DB::table('users')
            ->whereNull('role_id')
            ->update([
                'role_id' => $userRoleId,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No se revierte para no dejar usuarios existentes sin rol nuevamente.
    }
};
