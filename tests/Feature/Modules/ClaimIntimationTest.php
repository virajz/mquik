<?php

use App\Models\User;
use App\Modules\ClaimIntimation\Livewire\Edit;
use App\Modules\ClaimIntimation\Livewire\Index;
use App\Modules\ClaimIntimation\Models\ClaimIntimation;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ClaimIntimation::factory()->count(3)->create();

    $this->get(route('claim-intimation.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('claim-intimation.create'))
        ->assertOk()
        ->assertSeeLivewire(Edit::class);
});

it('creates a claim, stamps the number, and redirects', function () {
    Livewire::test(Edit::class)
        ->set('policy_no', 'pol-123')
        ->set('damage_nature', 'fire')
        ->set('survey_tat', 'within_48h')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('claim-intimation.index'));

    $claim = ClaimIntimation::first();
    expect($claim->intimation_no)->toBe('CI-'.str_pad((string) $claim->id, 5, '0', STR_PAD_LEFT))
        ->and($claim->policy_no)->toBe('POL-123')
        ->and($claim->damage_nature)->toBe('fire')
        ->and($claim->status)->toBe(ClaimIntimation::STATUS_PENDING);
});

it('requires an intimated-at timestamp when status is intimated', function () {
    Livewire::test(Edit::class)
        ->set('status', ClaimIntimation::STATUS_INTIMATED)
        ->call('save')
        ->assertHasErrors(['intimated_at']);

    Livewire::test(Edit::class)
        ->set('status', ClaimIntimation::STATUS_INTIMATED)
        ->set('intimated_at', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();
});

it('validates damage nature against the allowed list', function () {
    Livewire::test(Edit::class)
        ->set('damage_nature', 'nonsense')
        ->call('save')
        ->assertHasErrors(['damage_nature']);
});

it('filters by status and insurer', function () {
    $insurer = InsuranceCompanyMaster::factory()->create();
    $mine = ClaimIntimation::factory()->intimated()->create(['insurance_company_id' => $insurer->id]);
    $other = ClaimIntimation::factory()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', ClaimIntimation::STATUS_INTIMATED)
        ->assertSee($mine->intimation_no)
        ->assertDontSee($other->intimation_no);

    Livewire::test(Index::class)
        ->set('companyFilter', (string) $insurer->id)
        ->assertSee($mine->intimation_no)
        ->assertDontSee($other->intimation_no);
});

it('filters by survey TAT', function () {
    $a = ClaimIntimation::factory()->create(['survey_tat' => 'within_24h']);
    $b = ClaimIntimation::factory()->create(['survey_tat' => 'within_72h']);

    Livewire::test(Index::class)
        ->set('tatFilter', 'within_24h')
        ->assertSee($a->intimation_no)
        ->assertDontSee($b->intimation_no);
});

it('exports the survey report as CSV', function () {
    ClaimIntimation::factory()->intimated()->create();

    Livewire::test(Index::class)
        ->call('download')
        ->assertFileDownloaded();
});

it('counts pending, intimated and cancelled', function () {
    ClaimIntimation::factory()->create();
    ClaimIntimation::factory()->intimated()->create();
    ClaimIntimation::factory()->cancelled()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending'] === 1
            && $kpis['intimated'] === 1
            && $kpis['cancelled'] === 1);
});

it('deletes a claim from the index', function () {
    $claim = ClaimIntimation::factory()->create();

    Livewire::test(Index::class)->call('delete', $claim->id);

    expect(ClaimIntimation::find($claim->id))->toBeNull();
});

it('blocks the create page without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('claim_intimation.view');
    $this->actingAs($user);

    $this->get(route('claim-intimation.create'))->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('claim-intimation.index'))->assertRedirect(route('login'));
});
