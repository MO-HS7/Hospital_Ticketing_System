<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->enum('source', ['patient', 'reception', 'doctor', 'system'])->default('patient');
            $table->boolean('is_emergency')->default(false);
            
            // Payment-related fields (Phase 2, added now for schema completeness)
            $table->enum('payment_status', ['pending', 'paid', 'waived', 'refunded'])->default('pending');
            $table->string('payment_reference')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            
            // Journey tracking
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['patient_id', 'status']);
            $table->index(['payment_status']);
            $table->index(['started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};
