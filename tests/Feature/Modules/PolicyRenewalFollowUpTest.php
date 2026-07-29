<?php

use App\Modules\PolicyRenewalFollowUp\Livewire\Edit;
use App\Modules\PolicyRenewalFollowUp\Livewire\Index;
use App\Modules\PolicyRenewalFollowUp\Models\PolicyRenewalFollowUp;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    PolicyRenewalFollowUp::factory()->count(3)->create();

    $this->get(route('policy-renewal-follow-up.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('policy-renewal-follow-up.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('creates a renewal follow-up and stamps the IPR number', function () {
    Livewire::test(Edit::class)
        ->set('policy_number', 'pol-778899')
        ->set('policy_end_date', now()->addDays(10)->format('Y-m-d'))
        ->set('priority', 'high')
        ->set('reminder_frequency', 'before_7_days')
        ->set('renewal_premium', 18000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('policy-renewal-follow-up.index'));

    $f = PolicyRenewalFollowUp::first();
    expect($f->follow_up_no)->toBe('IPR-'.str_pad((string) $f->id, 5, '0', STR_PAD_LEFT))
        ->and($f->policy_number)->toBe('POL-778899');
});

it('stamps response_at and quote_shared_at as it advances', function () {
    Livewire::test(Edit::class)
        ->set('customer_response', 'quote_requested')
        ->set('status', PolicyRenewalFollowUp::STATUS_QUOTATION_SENT)
        ->call('save')
        ->assertHasNoErrors();

    $f = PolicyRenewalFollowUp::first();
    expect($f->response_at)->not->toBeNull()
        ->and($f->quote_shared_at)->not->toBeNull();
});

it('stamps policy_issued_at and payment_received_at when renewed', function () {
    Livewire::test(Edit::class)
        ->set('status', PolicyRenewalFollowUp::STATUS_RENEWED)
        ->call('save')
        ->assertHasNoErrors();

    $f = PolicyRenewalFollowUp::first();
    expect($f->policy_issued_at)->not->toBeNull()
        ->and($f->payment_received_at)->not->toBeNull();
});

it('requires a lost reason when the renewal is lost', function () {
    Livewire::test(Edit::class)
        ->set('status', PolicyRenewalFollowUp::STATUS_LOST)
        ->call('save')
        ->assertHasErrors(['lost_reason']);
});

it('requires an escalation reason when escalated', function () {
    Livewire::test(Edit::class)
        ->set('escalation', 'insurance_manager')
        ->call('save')
        ->assertHasErrors(['escalation_reason']);
});

it('reports expiring / overdue / lost / revenue KPIs', function () {
    PolicyRenewalFollowUp::factory()->create(['policy_end_date' => now()->addDays(3)]);  // expiring this week
    PolicyRenewalFollowUp::factory()->overdue()->create();                                // overdue
    PolicyRenewalFollowUp::factory()->renewed()->create(['renewal_premium' => 12000]);
    PolicyRenewalFollowUp::factory()->lost()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['expiring_week'] === 1
            && $kpis['overdue'] === 1
            && $kpis['lost'] === 1
            && (float) $kpis['revenue'] === 12000.0);
});

it('deletes a renewal follow-up', function () {
    $f = PolicyRenewalFollowUp::factory()->create();

    Livewire::test(Index::class)->call('delete', $f->id);

    expect(PolicyRenewalFollowUp::find($f->id))->toBeNull();
});

it('downloads the renewal follow-up report as a CSV stream', function () {
    PolicyRenewalFollowUp::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
