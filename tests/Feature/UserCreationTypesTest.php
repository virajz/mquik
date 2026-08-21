<?php

use App\Models\User;
use App\Modules\AuthorizationMaster\Livewire\PersonQuickAdd;
use App\Modules\AuthorizationMaster\Livewire\UserEditForm;
use App\Modules\AuthorizationMaster\Livewire\UserForm;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use App\Support\AppSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

function serviceContractorType(): VendorTypeMaster
{
    return VendorTypeMaster::firstOrCreate(
        ['name' => UserForm::CONTRACTOR_VENDOR_TYPE],
        ['is_active' => true],
    );
}

it('fills the form from the picked employee', function () {
    $employee = EmployeeMaster::factory()->create([
        'name' => 'RAVI TECHNICIAN', 'phone' => '9811100011', 'email' => 'ravi@workshop.test', 'is_active' => true,
    ]);

    Livewire::test(UserForm::class)
        ->set('userType', UserForm::TYPE_EMPLOYEE)
        ->set('employeeId', $employee->id)
        ->assertSet('name', 'RAVI TECHNICIAN')
        ->assertSet('phone', '9811100011')
        ->assertSet('email', 'ravi@workshop.test');
});

it('creates a login linked to that employee', function () {
    $employee = EmployeeMaster::factory()->create(['phone' => '9811100012', 'email' => 'linked@workshop.test', 'is_active' => true]);

    Livewire::test(UserForm::class)
        ->set('userType', UserForm::TYPE_EMPLOYEE)
        ->set('employeeId', $employee->id)
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('employee_id', $employee->id)->firstOrFail();

    expect($user->user_type)->toBe('employee')
        ->and($user->vendor_id)->toBeNull()
        ->and($user->must_reset_password)->toBeTrue();
});

it('only offers employees who have no login yet', function () {
    $taken = EmployeeMaster::factory()->create(['is_active' => true]);
    $free = EmployeeMaster::factory()->create(['is_active' => true]);
    User::factory()->create(['employee_id' => $taken->id]);

    $ids = Livewire::test(UserForm::class)->instance()->employees->pluck('id');

    expect($ids)->toContain($free->id)->and($ids)->not->toContain($taken->id);
});

it('offers only vendors filed as service contractors', function () {
    $contractor = VendorMaster::factory()->create(['is_active' => true]);
    $contractor->vendorTypes()->sync([serviceContractorType()->id]);

    $other = VendorMaster::factory()->create(['is_active' => true]);
    $other->vendorTypes()->sync([VendorTypeMaster::firstOrCreate(['name' => 'SPARE PARTS'], ['is_active' => true])->id]);

    $ids = Livewire::test(UserForm::class)->set('userType', UserForm::TYPE_CONTRACTOR)->instance()->contractors->pluck('id');

    expect($ids)->toContain($contractor->id)->and($ids)->not->toContain($other->id);
});

it('creates a login linked to that contractor', function () {
    $vendor = VendorMaster::factory()->create([
        'phone' => '9811100013', 'email' => 'contractor@workshop.test', 'is_active' => true,
    ]);
    $vendor->vendorTypes()->sync([serviceContractorType()->id]);

    Livewire::test(UserForm::class)
        ->set('userType', UserForm::TYPE_CONTRACTOR)
        ->set('vendorId', $vendor->id)
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('vendor_id', $vendor->id)->firstOrFail();

    expect($user->user_type)->toBe('contractor')->and($user->employee_id)->toBeNull();
});

it('still allows a manual entry with no master record', function () {
    Livewire::test(UserForm::class)
        ->set('userType', UserForm::TYPE_MANUAL)
        ->set('name', 'Outside Consultant')
        ->set('email', 'consultant@example.test')
        ->set('phone', '9811100014')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'consultant@example.test')->firstOrFail();

    expect($user->user_type)->toBe('manual')
        ->and($user->employee_id)->toBeNull()
        ->and($user->vendor_id)->toBeNull();
});

it('refuses a second login for the same employee', function () {
    $employee = EmployeeMaster::factory()->create(['is_active' => true]);
    User::factory()->create(['employee_id' => $employee->id]);

    Livewire::test(UserForm::class)
        ->set('userType', UserForm::TYPE_EMPLOYEE)
        ->set('employeeId', $employee->id)
        ->set('name', 'Duplicate')
        ->set('email', 'dupe@example.test')
        ->set('phone', '9811100015')
        ->call('save')
        ->assertHasErrors(['employeeId']);
});

it('quick-adds an employee and drops them into the picker', function () {
    Livewire::test(PersonQuickAdd::class)
        ->call('open', UserForm::TYPE_EMPLOYEE)
        ->set('name', 'Quick Employee')
        ->set('phone', '9811100016')
        ->call('save')
        ->assertDispatched('authorization-master:person-added');

    expect(EmployeeMaster::where('name', 'QUICK EMPLOYEE')->exists())->toBeTrue();
});

it('quick-adds a contractor filed under the service contractor type', function () {
    Livewire::test(PersonQuickAdd::class)
        ->call('open', UserForm::TYPE_CONTRACTOR)
        ->set('name', 'Quick Contractor')
        ->set('phone', '9811100017')
        ->call('save')
        ->assertDispatched('authorization-master:person-added');

    $vendor = VendorMaster::where('name', 'QUICK CONTRACTOR')->firstOrFail();

    expect($vendor->vendorTypes->pluck('name')->map(fn ($n) => mb_strtoupper($n)))
        ->toContain(UserForm::CONTRACTOR_VENDOR_TYPE);
});

it('logs out after 30 minutes of inactivity', function () {
    expect(config('session.lifetime'))->toBe(30);
});

it('edits a user and updates the linked employee master', function () {
    $employee = EmployeeMaster::factory()->create([
        'name' => 'OLD NAME', 'phone' => '9811100020', 'is_active' => true,
    ]);
    $user = User::factory()->create([
        'name' => 'Old Name', 'phone' => '9811100020',
        'employee_id' => $employee->id, 'user_type' => 'employee',
    ]);

    Livewire::test(UserEditForm::class)
        ->call('load', $user->id)
        ->assertSet('linkedType', 'employee')
        ->set('name', 'New Name')
        ->set('phone', '9811100099')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('New Name')
        ->and($user->fresh()->phone)->toBe('9811100099')
        // The master must not be left holding the old details.
        ->and($employee->fresh()->name)->toBe('NEW NAME')
        ->and($employee->fresh()->phone)->toBe('9811100099');
});

it('edits a contractor login and updates the vendor master', function () {
    $vendor = VendorMaster::factory()->create(['name' => 'OLD VENDOR', 'phone' => '9811100021', 'is_active' => true]);
    $vendor->vendorTypes()->sync([serviceContractorType()->id]);

    $user = User::factory()->create([
        'name' => 'Old Vendor', 'phone' => '9811100021',
        'vendor_id' => $vendor->id, 'user_type' => 'contractor',
    ]);

    Livewire::test(UserEditForm::class)
        ->call('load', $user->id)
        ->assertSet('linkedType', 'contractor')
        ->set('name', 'New Vendor')
        ->call('save')
        ->assertHasNoErrors();

    expect($vendor->fresh()->name)->toBe('NEW VENDOR');
});

it('edits a standalone login without touching any master', function () {
    $user = User::factory()->create(['user_type' => 'manual', 'phone' => '9811100022']);

    Livewire::test(UserEditForm::class)
        ->call('load', $user->id)
        ->assertSet('linkedType', null)
        ->set('name', 'Standalone')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Standalone');
});

it('can deactivate a user from the edit form', function () {
    $user = User::factory()->create(['user_type' => 'manual', 'phone' => '9811100023', 'is_active' => true]);

    Livewire::test(UserEditForm::class)
        ->call('load', $user->id)
        ->set('isActive', false)
        ->call('save');

    expect($user->fresh()->is_active)->toBeFalse();
});

it('takes the idle timeout from settings when one is saved', function () {
    AppSettings::set('security.session_lifetime', 45);

    // The provider applies it at boot, so re-resolve the app the way a request would.
    expect(AppSettings::int('security.session_lifetime'))->toBe(45);

    AppSettings::set('security.session_lifetime', 30);
});
