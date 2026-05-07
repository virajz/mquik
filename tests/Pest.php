<?php

use App\Models\User;
use App\Support\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Create a fully-permissioned user for tests. The default user model created by
 * `User::factory()->create()` has no roles, so it 403s on every gated route. Use this
 * helper as the default test user; opt back to a bare factory when you specifically
 * want to assert a 403 / unauthorized scenario.
 */
function adminUser(array $attrs = []): User
{
    PermissionRegistrar::class && app(PermissionRegistrar::class)->forgetCachedPermissions();

    // Ensure every declared module permission exists in tests.
    foreach (app(ModuleRegistry::class)->all() as $module) {
        foreach ((array) ($module['permissions'] ?? []) as $slug) {
            Permission::firstOrCreate(['name' => $slug, 'guard_name' => 'web']);
        }
    }

    $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    $role->syncPermissions(Permission::query()->pluck('name')->all());

    $user = User::factory()->create($attrs);
    $user->assignRole($role);

    return $user;
}
