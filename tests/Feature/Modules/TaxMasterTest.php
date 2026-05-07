<?php

use App\Modules\TaxMaster\Livewire\Form;
use App\Modules\TaxMaster\Livewire\Index;
use App\Modules\TaxMaster\Models\TaxMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    TaxMaster::factory()->count(3)->create();

    $this->get(route('tax-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name, code, or HSN/SAC', function () {
    TaxMaster::factory()->create(['name' => 'GSTTT TWELVE', 'code' => 'GSTAAA12', 'hsn_sac' => '8708']);
    TaxMaster::factory()->create(['name' => 'GSTTT EIGHTEEN', 'code' => 'GSTBBB18', 'hsn_sac' => '9987']);

    Livewire::test(Index::class)->set('search', 'GSTAAA12')
        ->assertSee('GSTTT TWELVE')
        ->assertDontSee('GSTTT EIGHTEEN');

    Livewire::test(Index::class)->set('search', '9987') // hsn_sac search
        ->assertSee('GSTTT EIGHTEEN')
        ->assertDontSee('GSTTT TWELVE');
});

it('filters by active status', function () {
    TaxMaster::factory()->create(['name' => 'TAX ENABLED', 'code' => 'TAXABC1']);
    TaxMaster::factory()->inactive()->create(['name' => 'TAX DISABLED', 'code' => 'TAXABC2']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('TAX ENABLED')
        ->assertDontSee('TAX DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('TAX DISABLED')
        ->assertDontSee('TAX ENABLED');
});

it('creates a tax with capital typing and persists numeric fields', function () {
    Livewire::test(Form::class)
        ->set('name', 'gst 18 percent')
        ->set('code', 'gst18')
        ->set('hsn_sac', '8708')
        ->set('gst_percent', '18.00')
        ->set('cess_percent', '0.00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('tax-master:saved');

    $record = TaxMaster::firstOrFail();
    expect($record->name)->toBe('GST 18 PERCENT')
        ->and($record->code)->toBe('GST18')
        ->and($record->hsn_sac)->toBe('8708')
        ->and((float) $record->gst_percent)->toBe(18.0)
        ->and((float) $record->cess_percent)->toBe(0.0)
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing tax', function () {
    $record = TaxMaster::factory()->create(['name' => 'OLD NAME', 'code' => 'OLDCODE']);

    Livewire::test(Form::class)
        ->dispatch('tax-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->set('gst_percent', '12.50')
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $record->fresh();
    expect($fresh->name)->toBe('UPDATED NAME')
        ->and((float) $fresh->gst_percent)->toBe(12.5);
});

it('deletes a tax from the index', function () {
    $record = TaxMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(TaxMaster::find($record->id))->toBeNull();
});

it('requires name, code, and gst_percent', function () {
    Livewire::test(Form::class)
        ->set('name', '')
        ->set('code', '')
        ->set('gst_percent', '')
        ->call('save')
        ->assertHasErrors(['name', 'code', 'gst_percent']);
});

it('blocks duplicate code', function () {
    TaxMaster::factory()->create(['code' => 'DUPCODE']);

    Livewire::test(Form::class)
        ->set('name', 'NEW TAX')
        ->set('code', 'DUPCODE')
        ->set('gst_percent', '5.00')
        ->call('save')
        ->assertHasErrors(['code' => 'unique']);
});

it('allows updating a tax without triggering self-uniqueness conflict', function () {
    $record = TaxMaster::factory()->create(['code' => 'GSTSELF']);

    Livewire::test(Form::class)
        ->dispatch('tax-master:edit', id: $record->id)
        ->set('code', 'GSTSELF')
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('tax-master.index'))->assertRedirect(route('login'));
});
