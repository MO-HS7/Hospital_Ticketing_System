<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds slot-based scheduling fields and updates status enum for slot-aware workflow.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Slot timing fields - check if they exist first
            if (!Schema::hasColumn('tickets', 'slot_start')) {
                $table->dateTime('slot_start')->nullable()->after('scheduled_at');
            }
            if (!Schema::hasColumn('tickets', 'slot_end')) {
                $table->dateTime('slot_end')->nullable()->after('slot_start');
            }
            if (!Schema::hasColumn('tickets', 'slot_duration')) {
                $table->integer('slot_duration')->default(30)->after('slot_end');
            }
            
            // SLA configuration fields
            if (!Schema::hasColumn('tickets', 'sla_response_minutes')) {
                $table->integer('sla_response_minutes')->default(30)->after('slot_duration');
            }
            if (!Schema::hasColumn('tickets', 'sla_resolution_minutes')) {
                $table->integer('sla_resolution_minutes')->default(1440)->after('sla_response_minutes');
            }
            
            // SLA tracking timestamps
            if (!Schema::hasColumn('tickets', 'response_started_at')) {
                $table->dateTime('response_started_at')->nullable()->after('accepted_at');
            }
            if (!Schema::hasColumn('tickets', 'resolution_started_at')) {
                $table->dateTime('resolution_started_at')->nullable()->after('response_started_at');
            }
            
            // Ticket source tracking - skip if already exists
            // (source column already exists from previous migration)
        });

        // Add indexes if they don't exist
        $indexes = collect(DB::select("SHOW INDEX FROM tickets"))->pluck('Key_name')->unique();
        
        Schema::table('tickets', function (Blueprint $table) use ($indexes) {
            if (!$indexes->contains('idx_doctor_slot')) {
                $table->index(['assigned_to', 'slot_start'], 'idx_doctor_slot');
            }
            if (!$indexes->contains('idx_dept_slot')) {
                $table->index(['department_id', 'slot_start'], 'idx_dept_slot');
            }
        });

        // Update status enum to include new slot-based statuses
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('scheduled', 'pending', 'in_queue', 'in_progress', 'completed', 'overdue', 'closed_late', 'cancelled', 'no_show', 'awaiting_payment', 'assigned') DEFAULT 'scheduled'");
        
        // Backfill existing tickets: use scheduled_at as slot_start if available
        DB::statement("
            UPDATE tickets 
            SET 
                slot_start = COALESCE(scheduled_at, created_at),
                slot_end = DATE_ADD(COALESCE(scheduled_at, created_at), INTERVAL COALESCE(slot_duration, 30) MINUTE)
            WHERE slot_start IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Drop indexes if they exist
            $indexes = collect(DB::select("SHOW INDEX FROM tickets"))->pluck('Key_name')->unique();
            if ($indexes->contains('idx_doctor_slot')) {
                $table->dropIndex('idx_doctor_slot');
            }
            if ($indexes->contains('idx_dept_slot')) {
                $table->dropIndex('idx_dept_slot');
            }
        });
        
        Schema::table('tickets', function (Blueprint $table) {
            $columns = ['slot_start', 'slot_end', 'slot_duration', 'sla_response_minutes', 'sla_resolution_minutes', 'response_started_at', 'resolution_started_at'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('tickets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        
        // Revert status enum
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'overdue', 'closed_late', 'awaiting_payment', 'assigned') DEFAULT 'pending'");
    }
};
