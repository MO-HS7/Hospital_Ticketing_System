<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add comprehensive indexes for query performance.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tickets table - additional indexes for common queries
        Schema::table('tickets', function (Blueprint $table) {
            $table->index('type', 'tickets_type_idx');
            $table->index('assigned_to', 'tickets_assigned_to_idx');
            $table->index('created_at', 'tickets_created_at_idx');
            $table->index('deadline', 'tickets_deadline_idx');
        });

        // Ticket events - for audit log queries
        Schema::table('ticket_events', function (Blueprint $table) {
            $table->index('ticket_id', 'ticket_events_ticket_id_idx');
            $table->index('created_at', 'ticket_events_created_at_idx');
            $table->index('event_type', 'ticket_events_event_type_idx');
        });

        // Ticket notes - for note lookups
        Schema::table('ticket_notes', function (Blueprint $table) {
            $table->index('ticket_id', 'ticket_notes_ticket_id_idx');
            $table->index('created_at', 'ticket_notes_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_type_idx');
            $table->dropIndex('tickets_assigned_to_idx');
            $table->dropIndex('tickets_created_at_idx');
            $table->dropIndex('tickets_deadline_idx');
        });

        Schema::table('ticket_events', function (Blueprint $table) {
            $table->dropIndex('ticket_events_ticket_id_idx');
            $table->dropIndex('ticket_events_created_at_idx');
            $table->dropIndex('ticket_events_event_type_idx');
        });

        Schema::table('ticket_notes', function (Blueprint $table) {
            $table->dropIndex('ticket_notes_ticket_id_idx');
            $table->dropIndex('ticket_notes_created_at_idx');
        });
    }
};
