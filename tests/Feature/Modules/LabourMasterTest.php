<?php

use App\Modules\LabourMaster\Livewire\Form;
use App\Modules\LabourMaster\Livewire\Index;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    LabourMaster::factory()->count(3)->create();

    $this->get(route('labour-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters records by search across name / code / hsn-sac', function () {
    LabourMaster::factory()->create(['name' => 'BRAKE PAD JOB', 'labour_code' => 'BPJ-001', 'hsn_sac_code' => '9988']);
    LabourMaster::factory()->create(['name' => 'AC GAS REFILL', 'labour_code' => 'AGR-001', 'hsn_sac_code' => '9989']);

    Livewire::test(Index::class)
        ->set('search', 'BRAKE')
        ->assertSee('BRAKE PAD JOB')
        ->assertDontSee('AC GAS REFILL')
        ->set('search', 'AGR-001')
        ->assertSee('AC GAS REFILL')
        ->assertDontSee('BRAKE PAD JOB')
        ->set('search', '9988')
        ->assertSee('BRAKE PAD JOB')
        ->assertDontSee('AC GAS REFILL');
});

it('filters by OSL vs in-house', function () {
    LabourMaster::factory()->create(['name' => 'IN HOUSE JOB QQQ', 'is_osl' => false]);
    LabourMaster::factory()->osl()->create(['name' => 'OUTSIDE JOB ZZZ']);

    Livewire::test(Index::class)
        ->set('oslFilter', 'osl')
        ->assertSee('OUTSIDE JOB ZZZ')
        ->assertDontSee('IN HOUSE JOB QQQ')
        ->set('oslFilter', 'in-house')
        ->assertSee('IN HOUSE JOB QQQ')
        ->assertDontSee('OUTSIDE JOB ZZZ');
});

it('creates a labour with capital typing', function () {
    Livewire::test(Form::class)
        ->set('name', 'engine oil change')
        ->set('labour_code', 'lb-new1')
        ->set('description', 'including filter swap')
        ->set('rate_before_tax', 500.00)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('labour-master:saved');

    $row = LabourMaster::first();
    expect($row->name)->toBe('ENGINE OIL CHANGE')
        ->and($row->labour_code)->toBe('LB-NEW1')
        ->and($row->description)->toBe('INCLUDING FILTER SWAP');
});

it('updates an existing labour and preserves identity', function () {
    $row = LabourMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('labour-master:edit', id: $row->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($row->fresh()->name)->toBe('UPDATED NAME');
});

it('rate including tax is computed live', function () {
    $tax = TaxMaster::factory()->create(['name' => 'GST 18%', 'gst_percent' => 18, 'cess_percent' => 0]);

    $component = Livewire::test(Form::class)
        ->set('rate_before_tax', 100.00)
        ->set('tax_id', $tax->id);

    expect($component->get('rateInclTax'))->toBe(118.0);
});

it('validates required name on save', function () {
    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

it('enforces unique labour_code', function () {
    LabourMaster::factory()->create(['labour_code' => 'DUP-LB']);

    Livewire::test(Form::class)
        ->set('name', 'NEW')
        ->set('labour_code', 'DUP-LB')
        ->call('save')
        ->assertHasErrors(['labour_code']);
});

it('deletes a labour from the index', function () {
    $row = LabourMaster::factory()->create();

    Livewire::test(Index::class)
        ->call('delete', $row->id);

    expect(LabourMaster::find($row->id))->toBeNull();
});

it('persists segment FK selection', function () {
    $segment = VehicleSegmentMaster::factory()->create(['name' => 'COMPACT SUV']);

    Livewire::test(Form::class)
        ->set('name', 'GENERAL')
        ->set('vehicle_segment_id', $segment->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(LabourMaster::first()->vehicle_segment_id)->toBe($segment->id);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('labour-master.index'))->assertRedirect(route('login'));
});
