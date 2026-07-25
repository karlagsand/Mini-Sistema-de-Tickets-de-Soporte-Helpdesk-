<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('internal_notifications')) {
            return;
        }

        Schema::create('internal_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->cascadeOnDelete();
            $table->string('type', 40)->default('info');
            $table->string('title', 160);
            $table->text('message')->nullable();
            $table->string('url', 500)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_notifications');
    }
};
