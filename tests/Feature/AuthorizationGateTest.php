<?php

use App\Livewire\SidebarMenu;
use App\Models\User;
use App\Modules\BusinessTypeMaster\Models\BusinessTypeMaster;
use App\Modules\CustomerMaster\Livewire\Edit as CustomerEdit;
use App\Modules\CustomerMaster\Livewire\Index as CustomerIndex;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\VehicleBrandMaster\Livewire\Form as VehicleBrandForm;
use App\Modules\VehicleBrandMaster\Livewire\Index as VehicleBrandIndex;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Support\ModuleRegistry;
use App\Support\SearchRegistry;
use Illuminate\Auth\Access\AuthorizationException;
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

/*
 |--------------------------------------------------------------------------
 | Inline authorize() coverage
 |--------------------------------------------------------------------------
 | Routes are gated by `can:` middleware, but Livewire actions can still be
 | invoked over the wire on a page the user has loaded. Every save() and
 | delete() across module Livewire components calls $this->authorize(...)
 | so a user who has `view` but not `create`/`update`/`delete` is blocked.
 */

// Note: Livewire's test broker catches AuthorizationException and converts it to a
// 403 response rather than propagating, so we assert the status (and the side
// effect not happening) instead of using Pest's ->throws().

it('Edit::save() blocks a user without create permission', function () {
    $businessType = BusinessTypeMaster::firstOrCreate(['name' => 'WALKING'], ['is_active' => true]);
    $countBefore = CustomerMaster::count();

    $user = User::factory()->create();
    $user->givePermissionTo('customer_master.view');
    $this->actingAs($user);

    Livewire::test(CustomerEdit::class)
        ->set('first_name', 'BLOCKED')
        ->set('business_type_id', $businessType->id)
        ->set('phone', '9999999999')
        ->call('save')
        ->assertStatus(403);

    expect(CustomerMaster::count())->toBe($countBefore);
});

it('Edit::save() succeeds with the create permission', function () {
    $businessType = BusinessTypeMaster::firstOrCreate(['name' => 'WALKING'], ['is_active' => true]);

    $user = User::factory()->create();
    $user->givePermissionTo(['customer_master.view', 'customer_master.create']);
    $this->actingAs($user);

    Livewire::test(CustomerEdit::class)
        ->set('first_name', 'OK')
        ->set('business_type_id', $businessType->id)
        ->set('phone', '9876543210')
        ->call('save')
        ->assertHasNoErrors();
});

it('Index::delete() blocks a user without delete permission', function () {
    $customer = CustomerMaster::factory()->create();

    $user = User::factory()->create();
    $user->givePermissionTo('customer_master.view');
    $this->actingAs($user);

    Livewire::test(CustomerIndex::class)
        ->call('delete', $customer->id)
        ->assertStatus(403);

    expect(CustomerMaster::find($customer->id))->not->toBeNull();
});

it('modal Form::save() also enforces inline authorize on simple-master modules', function () {
    $countBefore = VehicleBrandMaster::count();

    $user = User::factory()->create();
    $user->givePermissionTo('vehicle_brand_master.view');
    $this->actingAs($user);

    Livewire::test(VehicleBrandForm::class)
        ->set('name', 'BLOCKED BRAND')
        ->call('save')
        ->assertStatus(403);

    expect(VehicleBrandMaster::count())->toBe($countBefore);
});

it('modal Form::save() succeeds with the matching create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['vehicle_brand_master.view', 'vehicle_brand_master.create']);
    $this->actingAs($user);

    Livewire::test(VehicleBrandForm::class)
        ->set('name', 'NEW BRAND')
        ->call('save')
        ->assertHasNoErrors();

    expect(VehicleBrandMaster::where('name', 'NEW BRAND')->exists())->toBeTrue();
});

it('modal Index::delete() enforces inline authorize', function () {
    $brand = VehicleBrandMaster::factory()->create();

    $user = User::factory()->create();
    $user->givePermissionTo('vehicle_brand_master.view');
    $this->actingAs($user);

    Livewire::test(VehicleBrandIndex::class)
        ->call('delete', $brand->id)
        ->assertStatus(403);

    expect(VehicleBrandMaster::find($brand->id))->not->toBeNull();
});
