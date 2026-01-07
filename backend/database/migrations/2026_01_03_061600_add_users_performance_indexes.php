<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add performance indexes to users table.
 * Safely checks for column existence before adding indexes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if department_id column exists before adding index
            if (Schema::hasColumn('users', 'department_id')) {
                // Check if index doesn't already exist
                if (!$this->indexExists('users', 'users_department_id_idx')) {
                    $table->index('department_id', 'users_department_id_idx');
                }
            }

            // Check if activation_token column exists
            if (Schema::hasColumn('users', 'activation_token')) {
                if (!$this->indexExists('users', 'users_activation_token_idx')) {
                    $table->index('activation_token', 'users_activation_token_idx');
                }
            }

            // Check if is_active column exists
            if (Schema::hasColumn('users', 'is_active')) {
                if (!$this->indexExists('users', 'users_is_active_idx')) {
                    $table->index('is_active', 'users_is_active_idx');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Safely drop indexes if they exist
            if ($this->indexExists('users', 'users_department_id_idx')) {
                $table->dropIndex('users_department_id_idx');
            }
            if ($this->indexExists('users', 'users_activation_token_idx')) {
                $table->dropIndex('users_activation_token_idx');
            }
            if ($this->indexExists('users', 'users_is_active_idx')) {
                $table->dropIndex('users_is_active_idx');
            }
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
