<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the audit_events table for system-wide compliance-grade auditing.
     * This replaces ticket_events as the source of truth for all audit logging.
     */
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic auditing - short type names (ticket, user, payment, etc.)
            $table->string('auditable_type', 50);
            $table->unsignedBigInteger('auditable_id');
            
            // Actor (user who performed action, null = system)
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 50)->nullable(); // Denormalized snapshot
            
            // Event classification
            $table->string('event_type', 50);
            
            // Department for fast facets/filtering (UUID to match departments table)
            $table->uuid('department_id')->nullable();
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();
            
            // i18n summary support
            $table->string('summary_key', 100)->nullable();
            $table->json('summary_params')->nullable();
            
            // Change tracking
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->json('meta')->nullable();
            
            // Request metadata for compliance/investigation
            $table->string('ip_address', 45)->nullable(); // IPv6 compatible
            $table->text('user_agent')->nullable();
            $table->string('route', 255)->nullable();
            $table->string('method', 10)->nullable();
            $table->char('request_id', 36)->nullable(); // UUID
            
            // Timestamps in UTC (managed by Eloquent)
            $table->timestamps();
            
            // Composite indexes for common query patterns
            $table->index(['auditable_type', 'auditable_id', 'created_at'], 'idx_auditable_created');
            $table->index(['actor_id', 'created_at'], 'idx_actor_created');
            $table->index(['event_type', 'created_at'], 'idx_type_created');
            $table->index(['department_id', 'created_at'], 'idx_dept_created');
            $table->index('request_id', 'idx_request');
            $table->index('created_at', 'idx_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
