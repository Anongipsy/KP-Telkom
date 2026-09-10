<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD Section 13 & FR-11 - Notifications table
     *
     * Stores expiration alerts for contracts.
     * Prevents duplicate notifications for the same user + LOP + alert type.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('lop_reference');
            $table->string('alert_type');      // EXPIRING_SOON, OVERDUE
            $table->integer('days_remaining');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            // Prevent duplicate notifications per user for same LOP + alert type
            $table->unique(
                ['user_id', 'lop_reference', 'alert_type'],
                'notifications_user_lop_alert_unique'
            );
            $table->index('is_read');
            $table->index('alert_type');
            $table->index('lop_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
