<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD Section 13 & FR-17 - Sync Logs table
     *
     * Records Google API integration operations.
     * Direction: SHEETS_TO_APP or APP_TO_SHEETS
     */
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('sync_type');        // e.g., 'contracts', 'documents'
            $table->string('direction');         // SHEETS_TO_APP, APP_TO_SHEETS
            $table->string('status');            // success, failed, partial
            $table->integer('records_processed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->index('sync_type');
            $table->index('direction');
            $table->index('status');
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
