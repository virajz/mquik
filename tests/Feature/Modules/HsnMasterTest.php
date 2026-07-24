<?php

use App\Modules\HsnMaster\Livewire\Form;
use App\Modules\HsnMaster\Livewire\Index;
use App\Modules\HsnMaster\Models\HsnMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    HsnMaster::factory()->count(3)->create();

    $this->get(route('hsn-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('creates a code with kind and default rate', function () {
    Livewire::test(Form::class)
        ->set('code', '87082900')
        ->set('name', 'body parts and accessories')
        ->set('kind', HsnMaster::KIND_HSN)
        ->set('gst_percent', '28')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('hsn-master:saved');

    $row = HsnMaster::firstOrFail();
    expect($row->code)->toBe('87082900')
        ->and($row->name)->toBe('BODY PARTS AND ACCESSORIES')
        ->and($row->kind)->toBe(HsnMaster::KIND_HSN)
        ->and((float) $row->gst_percent)->toBe(28.0)
        ->and($row->label())->toBe('87082900 — BODY PARTS AND ACCESSORIES');
});

it('accepts 4, 6 and 8 digit codes but nothing else', function () {
    foreach (['8708', '998714', '87082900'] as $valid) {
        Livewire::test(Form::class)
            ->set('code', $valid)
            ->set('name', 'SOMETHING')
            ->call('save')
            ->assertHasNoErrors(['code']);
    }

    foreach (['870', '87085', '8708290012', 'ABCD'] as $invalid) {
        Livewire::test(Form::class)
            ->set('code', $invalid)
            ->set('name', 'SOMETHING ELSE')
            ->call('save')
            ->assertHasErrors(['code']);
    }
});

it('requires the code and keeps it unique', function () {
    HsnMaster::factory()->create(['code' => '87089900']);

    Livewire::test(Form::class)
        ->set('code', '')
        ->set('name', 'NO CODE')
        ->call('save')
        ->assertHasErrors(['code']);

    Livewire::test(Form::class)
        ->set('code', '87089900')
        ->set('name', 'DUPLICATE')
        ->call('save')
        ->assertHasErrors(['code' => 'unique']);
});

it('allows two codes to share a description', function () {
    // The code is the identity here — descriptions legitimately repeat.
    HsnMaster::factory()->create(['code' => '8708', 'name' => 'MOTOR PARTS']);

    Livewire::test(Form::class)
        ->set('code', '8714')
        ->set('name', 'MOTOR PARTS')
        ->call('save')
        ->assertHasNoErrors();

    expect(HsnMaster::where('name', 'MOTOR PARTS')->count())->toBe(2);
});

it('separates goods codes from service codes', function () {
    HsnMaster::factory()->create(['code' => '8708', 'kind' => HsnMaster::KIND_HSN]);
    HsnMaster::factory()->sac()->create(['code' => '998714']);

    expect(HsnMaster::where('kind', HsnMaster::KIND_HSN)->pluck('code')->all())->toBe(['8708'])
        ->and(HsnMaster::where('kind', HsnMaster::KIND_SAC)->pluck('code')->all())->toBe(['998714']);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('hsn-master.index'))->assertRedirect(route('login'));
});
