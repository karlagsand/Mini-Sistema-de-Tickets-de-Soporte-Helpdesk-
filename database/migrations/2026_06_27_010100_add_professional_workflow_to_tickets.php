<?php

use App\Models\Priority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tickets', 'priority_id')) {
            try {
                DB::statement('ALTER TABLE tickets DROP FOREIGN KEY tickets_priority_id_foreign');
            } catch (Throwable $exception) {
                // La llave puede tener otro nombre o ya no existir. Se continúa con el ajuste de columna.
            }

            DB::statement('ALTER TABLE tickets MODIFY priority_id BIGINT UNSIGNED NULL');

            try {
                DB::statement('ALTER TABLE tickets ADD CONSTRAINT tickets_priority_id_foreign FOREIGN KEY (priority_id) REFERENCES priorities(id) ON DELETE RESTRICT');
            } catch (Throwable $exception) {
                // Si la restricción ya existe, no detenemos la migración.
            }
        }

        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'request_type')) {
                $table->string('request_type', 30)->default('incidente')->after('description');
            }

            if (!Schema::hasColumn('tickets', 'reported_impact')) {
                $table->string('reported_impact', 80)->nullable()->after('request_type');
            }

            if (!Schema::hasColumn('tickets', 'impact')) {
                $table->string('impact', 20)->nullable()->after('priority_id');
            }

            if (!Schema::hasColumn('tickets', 'urgency')) {
                $table->string('urgency', 20)->nullable()->after('impact');
            }

            if (!Schema::hasColumn('tickets', 'priority_reviewed_at')) {
                $table->timestamp('priority_reviewed_at')->nullable()->after('urgency');
            }

            if (!Schema::hasColumn('tickets', 'first_responded_at')) {
                $table->timestamp('first_responded_at')->nullable()->after('opened_at');
            }

            if (!Schema::hasColumn('tickets', 'first_response_due_at')) {
                $table->timestamp('first_response_due_at')->nullable()->after('first_responded_at');
            }

            if (!Schema::hasColumn('tickets', 'resolution_due_at')) {
                $table->timestamp('resolution_due_at')->nullable()->after('first_response_due_at');
            }

            if (!Schema::hasColumn('tickets', 'satisfaction_rating')) {
                $table->unsignedTinyInteger('satisfaction_rating')->nullable()->after('closed_at');
            }

            if (!Schema::hasColumn('tickets', 'satisfaction_comment')) {
                $table->text('satisfaction_comment')->nullable()->after('satisfaction_rating');
            }

            if (!Schema::hasColumn('tickets', 'satisfaction_submitted_at')) {
                $table->timestamp('satisfaction_submitted_at')->nullable()->after('satisfaction_comment');
            }
        });
    }

    public function down(): void
    {
        $fallbackPriority = Priority::orderBy('level')->first();

        if ($fallbackPriority) {
            DB::table('tickets')->whereNull('priority_id')->update([
                'priority_id' => $fallbackPriority->id,
            ]);
        }

        Schema::table('tickets', function (Blueprint $table) {
            foreach ([
                'satisfaction_submitted_at',
                'satisfaction_comment',
                'satisfaction_rating',
                'resolution_due_at',
                'first_response_due_at',
                'first_responded_at',
                'priority_reviewed_at',
                'urgency',
                'impact',
                'reported_impact',
                'request_type',
            ] as $column) {
                if (Schema::hasColumn('tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('tickets', 'priority_id')) {
            try {
                DB::statement('ALTER TABLE tickets DROP FOREIGN KEY tickets_priority_id_foreign');
            } catch (Throwable $exception) {
                // Continuar.
            }

            DB::statement('ALTER TABLE tickets MODIFY priority_id BIGINT UNSIGNED NOT NULL');

            try {
                DB::statement('ALTER TABLE tickets ADD CONSTRAINT tickets_priority_id_foreign FOREIGN KEY (priority_id) REFERENCES priorities(id) ON DELETE RESTRICT');
            } catch (Throwable $exception) {
                // Continuar.
            }
        }
    }
};
