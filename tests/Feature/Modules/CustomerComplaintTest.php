<?php

use App\Modules\CustomerComplaint\Livewire\Edit;
use App\Modules\CustomerComplaint\Livewire\Index;
use App\Modules\CustomerComplaint\Models\CustomerComplaint;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    CustomerComplaint::factory()->count(3)->create();

    $this->get(route('customer-complaint.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('customer-complaint.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('registers a complaint and stamps opened_at', function () {
    Livewire::test(Edit::class)
        ->set('complaint_type', 'repeat_job')
        ->set('complaint_source', 'whatsapp')
        ->set('priority', 'high')
        ->set('description', 'same noise again')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('customer-complaint.index'));

    $c = CustomerComplaint::first();
    expect($c->complaint_no)->toBe('CMP-'.str_pad((string) $c->id, 5, '0', STR_PAD_LEFT))
        ->and($c->opened_at)->not->toBeNull()
        ->and($c->complaint_type)->toBe('repeat_job')
        ->and($c->description)->toBe('SAME NOISE AGAIN');
});

it('stamps assigned_at when an assignment is set', function () {
    Livewire::test(Edit::class)
        ->set('priority', 'normal')
        ->set('assignment', 'crm_executive')
        ->call('save')
        ->assertHasNoErrors();

    expect(CustomerComplaint::first()->assigned_at)->not->toBeNull();
});

it('requires an achieved satisfaction score to resolve', function () {
    Livewire::test(Edit::class)
        ->set('status', CustomerComplaint::STATUS_RESOLVED)
        ->call('save')
        ->assertHasErrors(['achieved_score']);
});

it('stamps closed_at and stores the score when resolved', function () {
    Livewire::test(Edit::class)
        ->set('status', CustomerComplaint::STATUS_RESOLVED)
        ->set('resolution_type', 'rework')
        ->set('achieved_score', 5)
        ->call('save')
        ->assertHasNoErrors();

    $c = CustomerComplaint::first();
    expect($c->closed_at)->not->toBeNull()
        ->and($c->achieved_score)->toBe(5);
});

it('rejects a satisfaction score outside 1-5', function () {
    Livewire::test(Edit::class)
        ->set('achieved_score', 9)
        ->call('save')
        ->assertHasErrors(['achieved_score']);
});

it('requires a priority', function () {
    Livewire::test(Edit::class)
        ->set('priority', '')
        ->call('save')
        ->assertHasErrors(['priority']);
});

it('reports received / open / resolved / avg-score KPIs', function () {
    CustomerComplaint::factory()->count(2)->create();                         // open (under investigation)
    CustomerComplaint::factory()->resolved()->create(['achieved_score' => 4]);
    CustomerComplaint::factory()->resolved()->create(['achieved_score' => 2]);

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['received'] === 4
            && $kpis['open'] === 2
            && $kpis['resolved'] === 2
            && (float) $kpis['avg_score'] === 3.0);
});

it('deletes a complaint', function () {
    $c = CustomerComplaint::factory()->create();

    Livewire::test(Index::class)->call('delete', $c->id);

    expect(CustomerComplaint::find($c->id))->toBeNull();
});

it('downloads the complaint report as a CSV stream', function () {
    CustomerComplaint::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
