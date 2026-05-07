<?php

use App\Models\User;
use App\Modules\AuthorizationMaster\Livewire\Form;
use App\Modules\AuthorizationMaster\Livewire\Index;
use App\Modules\AuthorizationMaster\Livewire\UserForm;
use App\Modules\AuthorizationMaster\Livewire\UserRolesForm;
use App\Modules\AuthorizationMaster\Livewire\Users;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the roles index page', function () {
    Role::firstOrCreate(['name' => 'Workshop Manager', 'guard_name' => 'web']);

    $this->get(route('authorization-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSee('Workshop Manager');
});

it('forbids non-admin users without authorization_master.view', function () {
    auth()->logout();

    // Bare user, no roles, no permissions.
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('authorization-master.index'))->assertForbidden();
});

it('renders the users index page', function () {
    User::factory()->create(['name' => 'Mechanic Joe', 'email' => 'joe@example.test']);

    $this->get(route('authorization-master.users'))
        ->assertOk()
        ->assertSeeLivewire(Users::class)
        ->assertSee('Mechanic Joe');
});

it('creates a new role with a chosen permission', function () {
    // Ensure a permission exists from the synced set.
    $perm = Permission::firstOrCreate(['name' => 'spare_brand_master.view', 'guard_name' => 'web']);

    Livewire::test(Form::class)
        ->set('name', 'Workshop Manager')
        ->set('permissions', [$perm->name])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('authorization-master:saved');

    $role = Role::where('name', 'Workshop Manager')->firstOrFail();
    expect($role->permissions->pluck('name')->all())->toContain('spare_brand_master.view');
});

it('updates an existing role permissions', function () {
    $role = Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web']);
    $existing = Permission::firstOrCreate(['name' => 'customer_master.view', 'guard_name' => 'web']);
    $newPerm = Permission::firstOrCreate(['name' => 'customer_master.create', 'guard_name' => 'web']);
    $role->syncPermissions([$existing->name]);

    Livewire::test(Form::class)
        ->dispatch('authorization-master:edit', id: $role->id)
        ->set('permissions', [$existing->name, $newPerm->name])
        ->call('save')
        ->assertHasNoErrors();

    expect($role->fresh()->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['customer_master.create', 'customer_master.view']);
});

it('forbids deleting the Super Admin role', function () {
    $superAdmin = Role::where('name', 'Super Admin')->firstOrFail();

    Livewire::test(Index::class)->call('delete', $superAdmin->id);

    expect(Role::find($superAdmin->id))->not->toBeNull();
});

it('forbids deleting a role that still has users attached', function () {
    $role = Role::firstOrCreate(['name' => 'Mechanic', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    Livewire::test(Index::class)->call('delete', $role->id);

    expect(Role::find($role->id))->not->toBeNull();
});

it('syncs user roles from the Users page', function () {
    $user = User::factory()->create(['name' => 'Plain Jane']);
    $roleA = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
    $roleB = Role::firstOrCreate(['name' => 'Mechanic', 'guard_name' => 'web']);

    Livewire::test(UserRolesForm::class)
        ->dispatch('authorization-master:manage-user', id: $user->id)
        ->set('selectedRoles', [$roleA->id, $roleB->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('authorization-master:user-roles-saved');

    expect($user->fresh()->roles->pluck('name')->sort()->values()->all())
        ->toBe(['Manager', 'Mechanic']);
});

it('validates the role name is required', function () {
    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

it('blocks duplicate role names', function () {
    Role::firstOrCreate(['name' => 'Existing Role', 'guard_name' => 'web']);

    Livewire::test(Form::class)
        ->set('name', 'Existing Role')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('deletes a role with no users attached', function () {
    $role = Role::firstOrCreate(['name' => 'Disposable', 'guard_name' => 'web']);

    Livewire::test(Index::class)->call('delete', $role->id);

    expect(Role::find($role->id))->toBeNull();
});

it('always keeps every permission on the Super Admin role even when unticked', function () {
    $superAdmin = Role::where('name', 'Super Admin')->firstOrFail();
    $totalPermissions = Permission::query()->count();

    Livewire::test(Form::class)
        ->dispatch('authorization-master:edit', id: $superAdmin->id)
        ->set('permissions', []) // try to wipe
        ->call('save')
        ->assertHasNoErrors();

    expect($superAdmin->fresh()->permissions()->count())->toBe($totalPermissions);
});

it('creates a user via the UserForm with auto-generated password', function () {
    $component = Livewire::test(UserForm::class)
        ->set('name', 'Ravi Sharma')
        ->set('email', 'ravi@example.com')
        ->call('save')
        ->assertHasNoErrors()
        ->assertNotDispatched('authorization-master:user-created') // deferred until dismiss
        ->assertSet('createdNotice', true);

    $user = User::where('email', 'ravi@example.com')->firstOrFail();
    expect($user->name)->toBe('Ravi Sharma')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('wrong-password', $user->password))->toBeFalse();

    // Dismissing the notice triggers the parent refresh event.
    $component
        ->call('dismissNotice')
        ->assertDispatched('authorization-master:user-created')
        ->assertSet('createdNotice', false);
});

it('creates a user with explicit password and roles', function () {
    $role = Role::firstOrCreate(['name' => 'Cashier', 'guard_name' => 'web']);

    Livewire::test(UserForm::class)
        ->set('name', 'Cashier One')
        ->set('email', 'cashier@example.com')
        ->set('password', 'manual-pw-12345')
        ->set('selectedRoles', [$role->id])
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'cashier@example.com')->firstOrFail();
    expect(Hash::check('manual-pw-12345', $user->password))->toBeTrue()
        ->and($user->hasRole('Cashier'))->toBeTrue();
});

it('reveals the temporary password to the admin after auto-generation', function () {
    $component = Livewire::test(UserForm::class)
        ->set('name', 'Auto Pw')
        ->set('email', 'auto@example.com')
        ->call('save');

    $shownPassword = $component->get('createdPassword');
    expect($shownPassword)->toBeString()
        ->and(strlen($shownPassword))->toBeGreaterThanOrEqual(12);

    $user = User::where('email', 'auto@example.com')->firstOrFail();
    expect(Hash::check($shownPassword, $user->password))->toBeTrue();
});

it('blocks duplicate email on user create', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(UserForm::class)
        ->set('name', 'Dupe')
        ->set('email', 'taken@example.com')
        ->call('save')
        ->assertHasErrors(['email']);
});

it('forbids user creation without authorization_master.create permission', function () {
    auth()->logout();
    $user = User::factory()->create();
    $user->givePermissionTo('authorization_master.view');
    $this->actingAs($user);

    Livewire::test(UserForm::class)
        ->set('name', 'Should Fail')
        ->set('email', 'shouldfail@example.com')
        ->call('save')
        ->assertForbidden();
});
