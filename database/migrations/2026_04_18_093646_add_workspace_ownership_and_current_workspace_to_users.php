<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            if (! Schema::hasColumn('workspaces', 'owner_id')) {
                $table->foreignId('owner_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_workspace_id')) {
                $table->foreignId('current_workspace_id')
                    ->nullable()
                    ->after('password')
                    ->constrained('workspaces')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }
        });

        $globalAdminRoleId = DB::table('roles')
            ->where('name', 'admin')
            ->whereNull('workspace_id')
            ->value('id');

        $users = DB::table('users')->select(['id', 'name', 'current_workspace_id'])->get();

        foreach ($users as $user) {
            $workspaceId = DB::table('role_user')
                ->where('user_id', $user->id)
                ->where('user_type', 'App\\Models\\User')
                ->whereNotNull('workspace_id')
                ->value('workspace_id');

            if (! $workspaceId) {
                $workspaceId = DB::table('workspaces')->insertGetId([
                    'name' => sprintf('%s Workspace #%d', $user->name, $user->id),
                    'display_name' => sprintf('%s Workspace', $user->name),
                    'description' => null,
                    'owner_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($globalAdminRoleId !== null) {
                    $hasAdminRole = DB::table('role_user')
                        ->where('role_id', $globalAdminRoleId)
                        ->where('user_id', $user->id)
                        ->where('user_type', 'App\\Models\\User')
                        ->where('workspace_id', $workspaceId)
                        ->exists();

                    if (! $hasAdminRole) {
                        DB::table('role_user')->insert([
                            'role_id' => $globalAdminRoleId,
                            'user_id' => $user->id,
                            'user_type' => 'App\\Models\\User',
                            'workspace_id' => $workspaceId,
                        ]);
                    }
                }
            } else {
                DB::table('workspaces')
                    ->where('id', $workspaceId)
                    ->whereNull('owner_id')
                    ->update(['owner_id' => $user->id, 'updated_at' => now()]);
            }

            if ($user->current_workspace_id === null) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['current_workspace_id' => $workspaceId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'current_workspace_id')) {
                $table->dropConstrainedForeignId('current_workspace_id');
            }
        });

        Schema::table('workspaces', function (Blueprint $table) {
            if (Schema::hasColumn('workspaces', 'owner_id')) {
                $table->dropConstrainedForeignId('owner_id');
            }
        });
    }
};
