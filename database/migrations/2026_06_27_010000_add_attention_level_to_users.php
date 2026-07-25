<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'position_level')) {
                $table->string('position_level', 50)->default('operativo')->after('role_id');
            }

            if (!Schema::hasColumn('users', 'attention_weight')) {
                $table->unsignedSmallInteger('attention_weight')->default(20)->after('position_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'attention_weight')) {
                $table->dropColumn('attention_weight');
            }

            if (Schema::hasColumn('users', 'position_level')) {
                $table->dropColumn('position_level');
            }
        });
    }
};
