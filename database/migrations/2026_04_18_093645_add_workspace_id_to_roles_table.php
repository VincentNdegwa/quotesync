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
        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'workspace_id')) {
                $table->foreignId('workspace_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('workspaces')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }
        });

        try {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_name_unique');
            });
        } catch (Throwable) {
            // The index may not exist on all environments.
        }

        try {
            Schema::table('roles', function (Blueprint $table) {
                $table->unique(['workspace_id', 'name'], 'roles_workspace_id_name_unique');
            });
        } catch (Throwable) {
            // The composite index may already exist.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_workspace_id_name_unique');
            });
        } catch (Throwable) {
            // The composite index may not exist on all environments.
        }

        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'workspace_id')) {
                $table->dropConstrainedForeignId('workspace_id');
            }

            $table->unique('name');
        });
    }
};
