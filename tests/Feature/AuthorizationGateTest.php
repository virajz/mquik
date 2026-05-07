<?php

use App\Livewire\SidebarMenu;
use App\Models\User;
use App\Support\ModuleRegistry;
use App\Support\SearchRegistry;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (app(ModuleRegistry::class)->all() as $module) {
        foreach ((array) ($module['permissions'] ?? []) as $slug) {
            Permission::firstOrCreate(['name' => $slug, 'guard_name' => 'web']);
        }
    }
});

it('a user with zero roles gets 403 on any module index route', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('customer-master.index'))->assertForbidden();
    $this->get(route('employee-master.index'))->assertForbidden();
    $this->get(route('vendor-master.index'))->assertForbidden();
});

it('a user with the Customers view permission gets 200 on customer index but 403 on others', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('customer_master.view');

    $this->actingAs($user);

    $this->get(route('customer-master.index'))->assertOk();
    $this->get(route('employee-master.index'))->assertForbidden();
});

it('a user with the Super Admin role passes every gate via Gate::before', function () {
    $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user);

    $this->get(route('customer-master.index'))->assertOk();
    $this->get(route('employee-master.index'))->assertOk();
    $this->get(route('vendor-master.index'))->assertOk();
});

it('SidebarMenu hides items the user cannot access', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('customer_master.view');
    $this->actingAs($user);

    Livewire::test(SidebarMenu::class)
        ->assertSee('Customers')
        ->assertDontSee('Employees')
        ->assertDontSee('Vendors');
});

it('SearchRegistry only returns sources the current user can view', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('customer_master.view');
    $this->actingAs($user);

    $sources = app(SearchRegistry::class)->sources()->pluck('module')->all();

    expect($sources)->toContain('CustomerMaster')
        ->and($sources)->not->toContain('EmployeeMaster')
        ->and($sources)->not->toContain('VendorMaster');
});

it('an unauthenticated visitor is redirected to login (auth middleware fires before can)', function () {
    $this->get(route('customer-master.index'))->assertRedirect(route('login'));
});
