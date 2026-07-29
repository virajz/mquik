<?php

use App\Modules\ServiceRecommendationFollowUp\Livewire\Edit;
use App\Modules\ServiceRecommendationFollowUp\Livewire\Index;
use App\Modules\ServiceRecommendationFollowUp\Models\ServiceRecommendationFollowUp;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ServiceRecommendationFollowUp::factory()->count(3)->create();

    $this->get(route('service-recommendation-follow-up.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('service-recommendation-follow-up.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('creates a recommendation and stamps recommended_at', function () {
    Livewire::test(Edit::class)
        ->set('recommended_service', 'timing belt replace')
        ->set('recommendation_type', 'engine')
        ->set('recommendation_category', 'safety')
        ->set('priority', 'high')
        ->set('estimated_value', 12000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('service-recommendation-follow-up.index'));

    $r = ServiceRecommendationFollowUp::first();
    expect($r->recommendation_no)->toBe('SRF-'.str_pad((string) $r->id, 5, '0', STR_PAD_LEFT))
        ->and($r->recommended_at)->not->toBeNull()
        ->and($r->recommended_service)->toBe('TIMING BELT REPLACE');
});

it('stamps informed_at and appointment_at as the status advances', function () {
    $rec = ServiceRecommendationFollowUp::factory()->create();

    Livewire::test(Edit::class, ['serviceRecommendationFollowUp' => $rec])
        ->set('status', ServiceRecommendationFollowUp::STATUS_APPOINTMENT_BOOKED)
        ->call('save')
        ->assertHasNoErrors();

    expect($rec->fresh()->appointment_at)->not->toBeNull();
});

it('requires a lost reason when lost', function () {
    Livewire::test(Edit::class)
        ->set('status', ServiceRecommendationFollowUp::STATUS_LOST)
        ->call('save')
        ->assertHasErrors(['lost_reason']);
});

it('requires an escalation reason when escalated', function () {
    Livewire::test(Edit::class)
        ->set('escalation', 'floor_gm_owner')
        ->call('save')
        ->assertHasErrors(['escalation_reason']);
});

it('reports open / safety / conversion / revenue KPIs', function () {
    ServiceRecommendationFollowUp::factory()->count(2)->create();               // open
    ServiceRecommendationFollowUp::factory()->safety()->create();               // open + safety
    ServiceRecommendationFollowUp::factory()->converted()->create(['estimated_value' => 8000]);
    ServiceRecommendationFollowUp::factory()->lost()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['open'] === 3
            && $kpis['safety'] === 1
            && $kpis['converted'] === 1
            && $kpis['lost'] === 1
            && (float) $kpis['revenue'] === 8000.0);
});

it('deletes a recommendation', function () {
    $r = ServiceRecommendationFollowUp::factory()->create();

    Livewire::test(Index::class)->call('delete', $r->id);

    expect(ServiceRecommendationFollowUp::find($r->id))->toBeNull();
});

it('downloads the recommended follow-up report as a CSV stream', function () {
    ServiceRecommendationFollowUp::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
