<?php

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\SalesInquiry\Livewire\Edit;
use App\Modules\SalesInquiry\Livewire\Index;
use App\Modules\SalesInquiry\Models\SalesInquiry;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    SalesInquiry::factory()->count(3)->create();

    $this->get(route('sales-inquiry.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('sales-inquiry.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('registers an inquiry and stamps inquiry_at', function () {
    Livewire::test(Edit::class)
        ->set('inquiry_type', 'general_service')
        ->set('inquiry_source', 'whatsapp')
        ->set('priority', 'high')
        ->set('estimated_value', 15000)
        ->set('inquiry_details', 'brake job')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('sales-inquiry.index'));

    $s = SalesInquiry::first();
    expect($s->inquiry_no)->toBe('INQ-'.str_pad((string) $s->id, 5, '0', STR_PAD_LEFT))
        ->and($s->inquiry_at)->not->toBeNull()
        ->and($s->inquiry_details)->toBe('BRAKE JOB');
});

it('stamps assigned_at when assigned and quotation_at when a quote is sent', function () {
    $emp = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('assigned_to_id', $emp->id)
        ->set('status', SalesInquiry::STATUS_QUOTATION_SENT)
        ->call('save')
        ->assertHasNoErrors();

    $s = SalesInquiry::first();
    expect($s->assigned_at)->not->toBeNull()
        ->and($s->quotation_at)->not->toBeNull();
});

it('stamps converted_at when converted', function () {
    Livewire::test(Edit::class)
        ->set('status', SalesInquiry::STATUS_CONVERTED)
        ->call('save')
        ->assertHasNoErrors();

    expect(SalesInquiry::first()->converted_at)->not->toBeNull();
});

it('requires a lost reason when the inquiry is lost', function () {
    Livewire::test(Edit::class)
        ->set('status', SalesInquiry::STATUS_LOST)
        ->call('save')
        ->assertHasErrors(['lost_reason']);
});

it('requires an escalation reason when escalated', function () {
    Livewire::test(Edit::class)
        ->set('escalation', 'gm_owner')
        ->call('save')
        ->assertHasErrors(['escalation_reason']);
});

it('reports open / quotation / conversion / revenue KPIs', function () {
    SalesInquiry::factory()->count(2)->create();                             // pending -> open
    SalesInquiry::factory()->quotationSent()->create();                      // open + quotation
    SalesInquiry::factory()->converted()->create(['estimated_value' => 9000]);
    SalesInquiry::factory()->lost()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['open'] === 3
            && $kpis['quotations'] === 1
            && $kpis['converted'] === 1
            && $kpis['lost'] === 1
            && (float) $kpis['conversion_rate'] === 20.0
            && (float) $kpis['revenue'] === 9000.0);
});

it('deletes an inquiry', function () {
    $s = SalesInquiry::factory()->create();

    Livewire::test(Index::class)->call('delete', $s->id);

    expect(SalesInquiry::find($s->id))->toBeNull();
});

it('downloads the sales inquiry report as a CSV stream', function () {
    SalesInquiry::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
