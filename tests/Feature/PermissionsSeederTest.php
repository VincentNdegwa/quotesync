<?php

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Support\Facades\Artisan;

test('permissions seeder creates permissions and assigns them to the admin role', function () {
    expect(Artisan::call('db:seed', ['--class' => PermissionsSeeder::class]))->toBe(0);

    $expectedPermissionNames = collect((new ReflectionClass(PermissionsSeeder::class))->getConstant('PERMISSIONS'))
        ->unique()
        ->values();

    expect(Permission::query()->whereIn('name', $expectedPermissionNames)->count())
        ->toBe($expectedPermissionNames->count());

    $adminRole = Role::query()
        ->where('name', 'admin')
        ->whereNull('workspace_id')
        ->first();

    expect($adminRole)->not->toBeNull();

    $adminPermissionNames = $adminRole->permissions()->pluck('name');

    expect($expectedPermissionNames->diff($adminPermissionNames))->toHaveCount(0);
});
