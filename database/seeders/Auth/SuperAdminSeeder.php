<?php

namespace Database\Seeders\Auth;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::query()->pluck('name')->all());

        // Grant Super Admin to every existing user. Safe for dev; admins can revoke from the UI.
        User::query()->each(function (User $user) use ($role) {
            if (! $user->hasRole($role->name)) {
                $user->assignRole($role);
            }
        });
    }
}
