<?php

use App\Models\User;
use App\Modules\InsuranceCompanyMaster\Livewire\Form;
use App\Modules\InsuranceCompanyMaster\Livewire\Index;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the index page', function () {
    InsuranceCompanyMaster::factory()->count(3)->create();

    $this->get(route('insurance-company-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name, short name, or gstin', function () {
    InsuranceCompanyMaster::factory()->create(['name' => 'NEWINDIA ASSURANCE', 'short_name' => 'NIA']);
    InsuranceCompanyMaster::factory()->create(['name' => 'ICICI LOMBARD', 'short_name' => 'ICICI', 'gstin' => '22ABCDE1234F1Z5']);
    InsuranceCompanyMaster::factory()->create(['name' => 'HDFC ERGO', 'short_name' => 'HDFC']);

    Livewire::test(Index::class)->set('search', 'NIA')
        ->assertSee('NEWINDIA ASSURANCE')
        ->assertDontSee('ICICI LOMBARD')
        ->assertDontSee('HDFC ERGO');

    Livewire::test(Index::class)->set('search', '22ABCDE')
        ->assertSee('ICICI LOMBARD')
        ->assertSee('22ABCDE1234F1Z5')
        ->assertDontSee('NEWINDIA ASSURANCE')
        ->assertDontSee('HDFC ERGO');
});

it('creates an insurance company with full details', function () {
    Livewire::test(Form::class)
        ->set('name', 'new india assurance')
        ->set('short_name', 'nia')
        ->set('gstin', '22AAAAA0000A1Z5')
        ->set('contact_person', 'ravi sharma')
        ->set('phone', '9876543210')
        ->set('email', 'claims@newindia.com')
        ->set('default_pass_percent', 75)
        ->set('address', 'mumbai office')
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('insurance-company-master:saved');

    $record = InsuranceCompanyMaster::firstOrFail();

    expect($record->name)->toBe('NEW INDIA ASSURANCE')
        ->and($record->short_name)->toBe('NIA')
        ->and($record->gstin)->toBe('22AAAAA0000A1Z5')
        ->and($record->contact_person)->toBe('RAVI SHARMA')
        ->and($record->email)->toBe('claims@newindia.com') // email NOT uppercased
        ->and((float) $record->default_pass_percent)->toBe(75.0)
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing record', function () {
    $record = InsuranceCompanyMaster::factory()->create([
        'name' => 'OLD NAME',
        'default_pass_percent' => 60,
    ]);

    Livewire::test(Form::class)
        ->dispatch('insurance-company-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->set('default_pass_percent', 80)
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $record->fresh();
    expect($fresh->name)->toBe('UPDATED NAME')
        ->and((float) $fresh->default_pass_percent)->toBe(80.0);
});

it('deletes a record from the index', function () {
    $record = InsuranceCompanyMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(InsuranceCompanyMaster::find($record->id))->toBeNull();
});

it('validates name is required', function () {
    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

it('validates pass percent is between 0 and 100', function () {
    Livewire::test(Form::class)
        ->set('name', 'TEST')
        ->set('default_pass_percent', 150)
        ->call('save')
        ->assertHasErrors(['default_pass_percent']);

    Livewire::test(Form::class)
        ->set('name', 'TEST')
        ->set('default_pass_percent', -5)
        ->call('save')
        ->assertHasErrors(['default_pass_percent']);
});

it('validates email format if provided', function () {
    Livewire::test(Form::class)
        ->set('name', 'TEST')
        ->set('email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['email' => 'email']);
});

it('validates gstin is exactly 15 chars if provided', function () {
    Livewire::test(Form::class)
        ->set('name', 'TEST')
        ->set('gstin', 'TOO-SHORT')
        ->call('save')
        ->assertHasErrors(['gstin']);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('insurance-company-master.index'))->assertRedirect(route('login'));
});
