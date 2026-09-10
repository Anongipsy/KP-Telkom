<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PRD Section 13 & FR-16 - Audit Logs table
     * Records user actions: login, contract updates, errors, etc.
     * MUST NOT log passwords, private keys, access tokens, or secrets.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action');           // login, failed_login, contract_update, contract_create, etc.
            $table->string('target_type')->nullable();      // e.g., 'contract', 'user'
            $table->string('target_reference')->nullable(); // e.g., LOP reference
            $table->json('metadata')->nullable();           // Additional context (no secrets!)
            $table->timestamps();

            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
