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
        $tableNames = config('permission.table_names', [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);

        $columnNames = config('permission.column_names', [
            'role_pivot_key' => 'role_id',
            'permission_pivot_key' => 'permission_id',
            'model_morph_key' => 'model_id',
            'team_foreign_key' => 'team_id',
        ]);

        // Permissions table
        if (!Schema::hasTable($tableNames['permissions'] ?? 'permissions')) {
            Schema::create($tableNames['permissions'] ?? 'permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        // Roles table
        if (!Schema::hasTable($tableNames['roles'] ?? 'roles')) {
            Schema::create($tableNames['roles'] ?? 'roles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        // Model has permissions
        if (!Schema::hasTable($tableNames['model_has_permissions'] ?? 'model_has_permissions')) {
            Schema::create($tableNames['model_has_permissions'] ?? 'model_has_permissions', function (Blueprint $table) use ($columnNames, $tableNames) {
                $table->unsignedBigInteger($columnNames['permission_pivot_key'] ?? 'permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key'] ?? 'model_id');
                $table->index([$columnNames['model_morph_key'] ?? 'model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');

                $table->foreign($columnNames['permission_pivot_key'] ?? 'permission_id')
                    ->references('id')
                    ->on($tableNames['permissions'] ?? 'permissions')
                    ->onDelete('cascade');

                $table->primary([$columnNames['permission_pivot_key'] ?? 'permission_id', $columnNames['model_morph_key'] ?? 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
            });
        }

        // Model has roles
        if (!Schema::hasTable($tableNames['model_has_roles'] ?? 'model_has_roles')) {
            Schema::create($tableNames['model_has_roles'] ?? 'model_has_roles', function (Blueprint $table) use ($columnNames, $tableNames) {
                $table->unsignedBigInteger($columnNames['role_pivot_key'] ?? 'role_id');
                $table->string('model_type');
                $table->unsignedBigInteger($columnNames['model_morph_key'] ?? 'model_id');
                $table->index([$columnNames['model_morph_key'] ?? 'model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');

                $table->foreign($columnNames['role_pivot_key'] ?? 'role_id')
                    ->references('id')
                    ->on($tableNames['roles'] ?? 'roles')
                    ->onDelete('cascade');

                $table->primary([$columnNames['role_pivot_key'] ?? 'role_id', $columnNames['model_morph_key'] ?? 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
            });
        }

        // Role has permissions
        if (!Schema::hasTable($tableNames['role_has_permissions'] ?? 'role_has_permissions')) {
            Schema::create($tableNames['role_has_permissions'] ?? 'role_has_permissions', function (Blueprint $table) use ($columnNames, $tableNames) {
                $table->unsignedBigInteger($columnNames['permission_pivot_key'] ?? 'permission_id');
                $table->unsignedBigInteger($columnNames['role_pivot_key'] ?? 'role_id');

                $table->foreign($columnNames['permission_pivot_key'] ?? 'permission_id')
                    ->references('id')
                    ->on($tableNames['permissions'] ?? 'permissions')
                    ->onDelete('cascade');

                $table->foreign($columnNames['role_pivot_key'] ?? 'role_id')
                    ->references('id')
                    ->on($tableNames['roles'] ?? 'roles')
                    ->onDelete('cascade');

                $table->primary([$columnNames['permission_pivot_key'] ?? 'permission_id', $columnNames['role_pivot_key'] ?? 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names', [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);

        Schema::dropIfExists($tableNames['role_has_permissions'] ?? 'role_has_permissions');
        Schema::dropIfExists($tableNames['model_has_roles'] ?? 'model_has_roles');
        Schema::dropIfExists($tableNames['model_has_permissions'] ?? 'model_has_permissions');
        Schema::dropIfExists($tableNames['roles'] ?? 'roles');
        Schema::dropIfExists($tableNames['permissions'] ?? 'permissions');
    }
};
