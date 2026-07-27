<?php

use App\Models\User;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\SurveyorInspection\Livewire\Edit;
use App\Modules\SurveyorInspection\Livewire\Index;
use App\Modules\SurveyorInspection\Models\SurveyorInspection;
use App\Modules\SurveyorInspection\Models\SurveyorInspectionItem;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    SurveyorInspection::factory()->count(3)->create();

    $this->get(route('surveyor-inspection.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('surveyor-inspection.create'))
        ->assertOk()
        ->assertSeeLivewire(Edit::class);
});

it('creates an inspection with an assessed line and redirects', function () {
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('surveyor_name', 'r k assessor')
        ->set('survey_type', 'final')
        ->call('addItem', 'spare')
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.description', 'front bumper')
        ->set('items.0.line_approval', 'replace')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('surveyor-inspection.index'));

    $si = SurveyorInspection::with('items')->first();
    expect($si->inspection_no)->toBe('SI-'.str_pad((string) $si->id, 5, '0', STR_PAD_LEFT))
        ->and($si->surveyor_name)->toBe('R K ASSESSOR')
        ->and($si->survey_type)->toBe('final')
        ->and($si->items)->toHaveCount(1)
        ->and($si->items->first()->line_approval)->toBe('replace')
        ->and($si->items->first()->description)->toBe('FRONT BUMPER');
});

it('requires a surveyed-at timestamp when completed', function () {
    Livewire::test(Edit::class)
        ->set('status', SurveyorInspection::STATUS_COMPLETED)
        ->call('save')
        ->assertHasErrors(['surveyed_at']);
});

it('validates survey type and approval against the allowed lists', function () {
    Livewire::test(Edit::class)
        ->set('survey_type', 'nope')
        ->set('surveyor_approval', 'nope')
        ->call('save')
        ->assertHasErrors(['survey_type', 'surveyor_approval']);
});

it('requires a description on every assessed line', function () {
    Livewire::test(Edit::class)
        ->call('addItem', 'labour')
        ->set('items.0.description', '')
        ->call('save')
        ->assertHasErrors(['items.0.description']);
});

it('filters by status, type and insurer', function () {
    $insurer = InsuranceCompanyMaster::factory()->create();
    $mine = SurveyorInspection::factory()->completed()->create([
        'insurance_company_id' => $insurer->id,
        'survey_type' => 'spot',
    ]);
    $other = SurveyorInspection::factory()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', SurveyorInspection::STATUS_COMPLETED)
        ->assertSee($mine->inspection_no)
        ->assertDontSee($other->inspection_no);

    Livewire::test(Index::class)
        ->set('typeFilter', 'spot')
        ->assertSee($mine->inspection_no)
        ->assertDontSee($other->inspection_no);

    Livewire::test(Index::class)
        ->set('companyFilter', (string) $insurer->id)
        ->assertSee($mine->inspection_no)
        ->assertDontSee($other->inspection_no);
});

it('exports the surveyor inspection report as CSV', function () {
    SurveyorInspection::factory()->completed()->create();

    Livewire::test(Index::class)
        ->call('download')
        ->assertFileDownloaded();
});

it('counts by status', function () {
    SurveyorInspection::factory()->create();
    SurveyorInspection::factory()->inProgress()->create();
    SurveyorInspection::factory()->completed()->create();
    SurveyorInspection::factory()->cancelled()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending'] === 1
            && $kpis['in_progress'] === 1
            && $kpis['completed'] === 1
            && $kpis['cancelled'] === 1);
});

it('deletes an inspection and cascades its lines', function () {
    $si = SurveyorInspection::factory()->create();
    $si->items()->create(['line_type' => 'labour', 'description' => 'X', 'quantity' => 1, 'sequence_no' => 1]);

    Livewire::test(Index::class)->call('delete', $si->id);

    expect(SurveyorInspection::find($si->id))->toBeNull()
        ->and(SurveyorInspectionItem::where('surveyor_inspection_id', $si->id)->count())->toBe(0);
});

it('blocks the create page without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('surveyor_inspection.view');
    $this->actingAs($user);

    $this->get(route('surveyor-inspection.create'))->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('surveyor-inspection.index'))->assertRedirect(route('login'));
});
