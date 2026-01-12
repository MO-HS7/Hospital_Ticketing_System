<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add performance indexes for audit log queries.
     * Supports efficient filtering by user, event type, ticket, and date.
     */
    public function up(): void
    {
        // Get existing indexes
        $indexes = collect(DB::select("SHOW INDEX FROM ticket_events"))->pluck('Key_name')->unique();

        Schema::table('ticket_events', function (Blueprint $table) use ($indexes) {
            // Index for filtering by user + date (actor filter)
            if (!$indexes->contains('idx_events_user_created')) {
                $table->index(['user_id', 'created_at'], 'idx_events_user_created');
            }

            // Index for filtering by event type + date (action type filter)
            if (!$indexes->contains('idx_events_type_created')) {
                $table->index(['event_type', 'created_at'], 'idx_events_type_created');
            }

            // Index for filtering by ticket + date (ticket-centric queries)
            if (!$indexes->contains('idx_events_ticket_created')) {
                $table->index(['ticket_id', 'created_at'], 'idx_events_ticket_created');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_events', function (Blueprint $table) {
            $indexes = collect(DB::select("SHOW INDEX FROM ticket_events"))->pluck('Key_name')->unique();
            
            if ($indexes->contains('idx_events_user_created')) {
                $table->dropIndex('idx_events_user_created');
            }
            if ($indexes->contains('idx_events_type_created')) {
                $table->dropIndex('idx_events_type_created');
            }
            if ($indexes->contains('idx_events_ticket_created')) {
                $table->dropIndex('idx_events_ticket_created');
            }
        });
    }
};
