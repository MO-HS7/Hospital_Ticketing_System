<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('pending','in_progress','completed','overdue','closed_late','awaiting_payment','assigned') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('tickets')
            ->whereIn('status', ['awaiting_payment', 'assigned'])
            ->update(['status' => 'pending']);

        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('pending','in_progress','completed','overdue','closed_late') NOT NULL DEFAULT 'pending'");
    }
};
