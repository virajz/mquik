<?php

use App\Models\User;
use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry;
use App\Modules\IpiReport\Livewire\Index;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the report page', function () {
    InternalPartsInquiry::factory()->count(3)->create();

    $this->get(route('ipi-report.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters by status', function () {
    $ordered = InternalPartsInquiry::factory()->ordered()->create();
    $pending = InternalPartsInquiry::factory()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', InternalPartsInquiry::STATUS_ORDERED)
        ->assertSee($ordered->ipi_no)
        ->assertDontSee($pending->ipi_no);
});

it('filters by inquiry type', function () {
    $jc = InternalPartsInquiry::factory()->create(['inquiry_type' => 'against_job_card']);
    $stock = InternalPartsInquiry::factory()->create(['inquiry_type' => 'stock_replenishment']);

    Livewire::test(Index::class)
        ->set('typeFilter', 'stock_replenishment')
        ->assertSee($stock->ipi_no)
        ->assertDontSee($jc->ipi_no);
});

it('exports the filtered set as CSV', function () {
    InternalPartsInquiry::factory()->ordered()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', InternalPartsInquiry::STATUS_ORDERED)
        ->call('download')
        ->assertFileDownloaded();
});

it('blocks export without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('ipi_report.view');
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('download')
        ->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('ipi-report.index'))->assertRedirect(route('login'));
});
