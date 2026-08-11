<?php

use App\Modules\Appointment\Models\Appointment;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\GoodsHandover\Livewire\Edit as HandoverEdit;
use App\Modules\GoodsReceipt\Models\GoodsReceipt;
use App\Modules\InternalPartOrder\Livewire\Edit as IpoEdit;
use App\Modules\InternalPartOrder\Models\InternalPartOrder;
use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry;
use App\Modules\JobCard\Livewire\Edit as JobCardEdit;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\VendorPurchaseOrder\Livewire\Edit as VpoEdit;
use Livewire\Livewire;

/**
 * The inward chain: every hand-off from the vehicle arriving at the gate through
 * to parts reaching the floor. Each step must carry its source forward — losing
 * the link is what leaves records orphaned.
 */
beforeEach(function () {
    $this->actingAs(adminUser());
});

it('raises a job card from a gate visit, carrying the customer and vehicle', function () {
    // Give the visit a real identity — comparing two nulls would pass without
    // proving anything.
    $vehicle = CustomerVehicleMaster::factory()->create();
    $gate = GateInOut::factory()->create([
        'customer_id' => $vehicle->customer_id,
        'customer_vehicle_id' => $vehicle->id,
    ]);

    $component = Livewire::test(JobCardEdit::class, ['fromGateEvent' => $gate->id]);

    expect($component->get('gate_event_id'))->toBe($gate->id)
        ->and($component->get('customer_vehicle_id'))->toBe($vehicle->id)
        ->and($component->get('customer_id'))->toBe($vehicle->customer_id);
});

it('persists the source links when a job card is saved', function () {
    // Regression: neither id was in the validation rules, and save() builds its
    // payload from validate() — so both links were silently dropped.
    // The factory leaves the visit unidentified, so name the vehicle explicitly —
    // this test is about the links persisting, not about the prefill.
    $vehicle = CustomerVehicleMaster::factory()->create();
    $gate = GateInOut::factory()->create([
        'customer_id' => $vehicle->customer_id,
        'customer_vehicle_id' => $vehicle->id,
    ]);
    $appointment = Appointment::factory()->create();

    Livewire::test(JobCardEdit::class, ['fromGateEvent' => $gate->id])
        ->set('appointment_id', $appointment->id)
        ->set('workshop_department_id', $appointment->workshop_department_id)
        ->set('assigned_advisor_id', $appointment->assigned_advisor_id)
        ->call('save')
        ->assertHasNoErrors();

    $card = JobCard::latest('id')->first();

    expect($card->gate_event_id)->toBe($gate->id)
        ->and($card->appointment_id)->toBe($appointment->id);
});

it('carries an internal parts inquiry forward into a store order', function () {
    $ipi = InternalPartsInquiry::factory()->create();
    $ipi->items()->create(['description' => 'BRAKE PAD', 'quantity' => 3, 'sequence_no' => 1]);

    $component = Livewire::test(IpoEdit::class, ['fromIpi' => $ipi->id]);
    $items = $component->get('items');

    expect($component->get('internal_parts_inquiry_id'))->toBe($ipi->id)
        ->and($component->get('job_card_id'))->toBe($ipi->job_card_id)
        ->and($items)->toHaveCount(1)
        ->and($items[0]['description'])->toBe('BRAKE PAD')
        ->and((float) $items[0]['qty_requested'])->toBe(3.0);
});

it('escalates only the unissued quantity from a store order to a vendor order', function () {
    $ipo = InternalPartOrder::factory()->create();
    $ipo->items()->create([
        'description' => 'BRAKE PAD', 'qty_requested' => 10, 'qty_issued' => 4,
        'issue_status' => 'partially_issued', 'sequence_no' => 1,
    ]);

    $component = Livewire::test(VpoEdit::class, ['fromIpo' => $ipo->id]);
    $items = $component->get('items');

    expect($component->get('internal_part_order_id'))->toBe($ipo->id)
        ->and($items)->toHaveCount(1)
        // 10 requested − 4 issued: only the shortfall needs buying in.
        ->and((float) $items[0]['quantity'])->toBe(6.0);
});

it('hands received goods to the floor with verification reset for the second check', function () {
    $grn = GoodsReceipt::factory()->create();
    $grn->items()->create([
        'description' => 'OIL FILTER', 'quantity' => 5,
        'material_condition' => 'new', 'physical_verification' => 'verified', 'sequence_no' => 1,
    ]);

    $component = Livewire::test(HandoverEdit::class, ['fromGrn' => $grn->id]);
    $items = $component->get('items');

    expect($component->get('goods_receipt_id'))->toBe($grn->id)
        ->and($items)->toHaveCount(1)
        ->and((float) $items[0]['quantity'])->toBe(5.0)
        ->and($items[0]['material_condition'])->toBe('new')
        // The floor verifies independently — the store's tick must not carry over.
        ->and($items[0]['physical_verification'])->toBeNull();
});
