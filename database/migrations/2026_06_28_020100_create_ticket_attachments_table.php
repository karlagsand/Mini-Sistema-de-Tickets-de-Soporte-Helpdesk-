<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_attachments')) {
            return;
        }

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->index(['ticket_id', 'is_internal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
    }
};
