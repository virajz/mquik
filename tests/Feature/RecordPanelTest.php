<?php

use App\Livewire\RecordPanel;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\JobCard\Livewire\Index as JobCardIndex;
use App\Modules\JobCard\Models\JobCard;
use App\Support\RelatedRecords;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('lists related areas with live counts for a customer', function () {
    $customer = CustomerMaster::factory()->create();
    JobCard::factory()->count(2)->create(['customer_id' => $customer->id]);

    $areas = collect(RelatedRecords::for(RelatedRecords::SUBJECT_CUSTOMER, $customer->id))
        ->keyBy('label');

    expect($areas)->toHaveKey('Job Cards')
        ->and($areas['Job Cards']['count'])->toBe(2)
        ->and($areas['Job Cards']['url'])->toContain('customer='.$customer->id);
});

it('counts nothing for a record with no related work', function () {
    $customer = CustomerMaster::factory()->create();

    $counts = collect(RelatedRecords::for(RelatedRecords::SUBJECT_CUSTOMER, $customer->id))
        ->pluck('count')
        ->unique();

    expect($counts->all())->toBe([0]);
});

it('renders the panel for a customer', function () {
    $customer = CustomerMaster::factory()->create();
    JobCard::factory()->create(['customer_id' => $customer->id]);

    Livewire::test(RecordPanel::class, [
        'subject' => RelatedRecords::SUBJECT_CUSTOMER,
        'recordId' => $customer->id,
        'recordLabel' => 'ACME',
    ])->assertSee('Job Cards')->assertSee('Related Areas');
});

it('returns no areas without a record id', function () {
    Livewire::test(RecordPanel::class, ['subject' => RelatedRecords::SUBJECT_CUSTOMER])
        ->assertSee('Nothing linked yet');
});

it('scopes the job card index to a customer via the deep link', function () {
    $mine = CustomerMaster::factory()->create();
    $theirs = CustomerMaster::factory()->create();

    $a = JobCard::factory()->create(['customer_id' => $mine->id]);
    $b = JobCard::factory()->create(['customer_id' => $theirs->id]);

    Livewire::withQueryParams(['customer' => $mine->id])
        ->test(JobCardIndex::class)
        ->assertSee($a->job_card_no)
        ->assertDontSee($b->job_card_no);
});

it('scopes the job card index to a vehicle via the deep link', function () {
    $vehicle = CustomerVehicleMaster::factory()->create();

    $mine = JobCard::factory()->create(['customer_vehicle_id' => $vehicle->id]);
    $other = JobCard::factory()->create();

    Livewire::withQueryParams(['vehicle' => $vehicle->id])
        ->test(JobCardIndex::class)
        ->assertSee($mine->job_card_no)
        ->assertDontSee($other->job_card_no);
});

it('shows the whole list once the record scope is cleared', function () {
    $customer = CustomerMaster::factory()->create();
    $mine = JobCard::factory()->create(['customer_id' => $customer->id]);
    $other = JobCard::factory()->create();

    Livewire::withQueryParams(['customer' => $customer->id])
        ->test(JobCardIndex::class)
        ->assertDontSee($other->job_card_no)
        ->call('clearRecordScope')
        ->assertSee($mine->job_card_no)
        ->assertSee($other->job_card_no);
});
