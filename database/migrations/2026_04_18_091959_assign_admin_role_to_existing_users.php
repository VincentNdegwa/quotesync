<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_user')) {
            return;
        }

        $adminRoleId = DB::table('roles')
            ->where('name', 'admin')
            ->value('id');

        if ($adminRoleId === null) {
            $adminRoleId = DB::table('roles')->insertGetId([
                'name' => 'admin',
                'display_name' => 'Admin',
                'description' => 'Default administrator role.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $userIds = DB::table('users')->pluck('id');

        foreach ($userIds as $userId) {
            $alreadyAssigned = DB::table('role_user')
                ->where('role_id', $adminRoleId)
                ->where('user_id', $userId)
                ->where('user_type', User::class)
                ->whereNull('workspace_id')
                ->exists();

            if (! $alreadyAssigned) {
                DB::table('role_user')->insert([
                    'role_id' => $adminRoleId,
                    'user_id' => $userId,
                    'user_type' => User::class,
                    'workspace_id' => null,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_user')) {
            return;
        }

        $adminRoleId = DB::table('roles')
            ->where('name', 'admin')
            ->value('id');

        if ($adminRoleId === null) {
            return;
        }

        DB::table('role_user')
            ->where('role_id', $adminRoleId)
            ->where('user_type', User::class)
            ->whereNull('workspace_id')
            ->delete();

        $roleStillUsed = DB::table('role_user')
            ->where('role_id', $adminRoleId)
            ->exists();

        if (! $roleStillUsed) {
            DB::table('roles')->where('id', $adminRoleId)->delete();
        }
    }
};
