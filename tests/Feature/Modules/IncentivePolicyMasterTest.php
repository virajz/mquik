<?php

use App\Modules\IncentivePolicyMaster\Livewire\Form;
use App\Modules\IncentivePolicyMaster\Livewire\Index;
use App\Modules\IncentivePolicyMaster\Models\IncentivePolicyMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    IncentivePolicyMaster::factory()->count(3)->create();

    $this->get(route('incentive-policy-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('incentive-policy-master.index'))->assertRedirect(route('login'));
});

it('creates a policy with a basis and rate', function () {
    Livewire::test(Form::class)
        ->set('name', 'parts sales incentive')
        ->set('code', 'part')
        ->set('basis', 'parts_sales')
        ->set('rate_percent', 2.5)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('incentive-policy-master:saved');

    $p = IncentivePolicyMaster::firstOrFail();
    expect($p->name)->toBe('PARTS SALES INCENTIVE')
        ->and($p->basis)->toBe('parts_sales')
        ->and((float) $p->rate_percent)->toBe(2.5);
});

it('validates basis against the allowed set', function () {
    Livewire::test(Form::class)
        ->set('name', 'bad policy')
        ->set('basis', 'nonsense')
        ->call('save')
        ->assertHasErrors(['basis']);
});

it('deletes a policy from the index', function () {
    $record = IncentivePolicyMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $record->id);
    expect(IncentivePolicyMaster::find($record->id))->toBeNull();
});
