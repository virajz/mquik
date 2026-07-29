<?php

use App\Modules\VehicleAmc\Livewire\Edit;
use App\Modules\VehicleAmc\Livewire\Index;
use App\Modules\VehicleAmc\Models\VehicleAmc;
use App\Support\FinancialYear;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VehicleAmc::factory()->count(3)->create();

    $this->get(route('vehicle-amc.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('vehicle-amc.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('creates an AMC with an MQ/AMC FY series number and an included item', function () {
    Livewire::test(Edit::class)
        ->set('amc_package', 'gold')
        ->set('amc_validity', '12_months')
        ->set('services_limit', 4)
        ->set('amount', 15000)
        ->set('payment_status', 'fully_paid')
        ->set('items.0.item_type', 'labour')
        ->set('items.0.description', 'free general service')
        ->set('items.0.quantity', 4)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('vehicle-amc.index'));

    $a = VehicleAmc::with('items')->first();
    $fy = FinancialYear::label($a->created_at);
    expect($a->amc_no)->toBe('MQ/AMC/'.$fy.'/0001')
        ->and($a->fy_label)->toBe($fy)
        ->and($a->items)->toHaveCount(1)
        ->and($a->items->first()->description)->toBe('FREE GENERAL SERVICE');
});

it('increments the FY sequence per financial year', function () {
    VehicleAmc::factory()->create();
    VehicleAmc::factory()->create();

    $fy = FinancialYear::label(now());
    $all = VehicleAmc::orderBy('id')->get();
    expect($all[0]->amc_no)->toBe('MQ/AMC/'.$fy.'/0001')
        ->and($all[1]->amc_no)->toBe('MQ/AMC/'.$fy.'/0002');
});

it('computes remaining services', function () {
    $amc = VehicleAmc::factory()->create(['services_limit' => 4, 'services_availed' => 1]);
    expect($amc->servicesRemaining())->toBe(3);

    $noLimit = VehicleAmc::factory()->create(['services_limit' => null]);
    expect($noLimit->servicesRemaining())->toBeNull();
});

it('rejects an end date before the start date', function () {
    Livewire::test(Edit::class)
        ->set('start_date', '2026-07-24')
        ->set('end_date', '2026-07-01')
        ->call('save')
        ->assertHasErrors(['end_date']);
});

it('requires a status and payment status', function () {
    Livewire::test(Edit::class)
        ->set('status', '')
        ->set('payment_status', '')
        ->call('save')
        ->assertHasErrors(['status', 'payment_status']);
});

it('reports active / renewals-due / services / revenue KPIs', function () {
    VehicleAmc::factory()->count(2)->create();                       // active, fully paid
    VehicleAmc::factory()->renewalDue()->create();                  // active, expiring this week
    VehicleAmc::factory()->expired()->create();                     // expired
    VehicleAmc::factory()->create(['services_availed' => 3]);

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['active'] === 4 && $kpis['renewals_due'] === 1 && $kpis['services_availed'] === 3);
});

it('deletes an AMC', function () {
    $a = VehicleAmc::factory()->create();

    Livewire::test(Index::class)->call('delete', $a->id);

    expect(VehicleAmc::find($a->id))->toBeNull();
});

it('downloads the AMC report as a CSV stream', function () {
    VehicleAmc::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
