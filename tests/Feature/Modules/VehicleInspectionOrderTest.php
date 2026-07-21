<?php

use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\VehicleInspectionOrder\Livewire\Edit;
use App\Modules\VehicleInspectionOrder\Livewire\Index;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use App\Modules\WorkOrderHoldReasonMaster\Models\WorkOrderHoldReasonMaster;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VehicleInspectionOrder::factory()->count(2)->create();

    $this->get(route('vehicle-inspection-order.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('vehicle-inspection-order.index'))->assertRedirect(route('login'));
});

it('creates a work order, stamps VIO number, and redirects into the editor', function () {
    $jobCard = JobCard::factory()->create();

    $high = PriorityMaster::factory()->create([
        'name' => 'HIGH', 'sort_order' => 20, 'applies_to' => 'both',
    ]);

    Livewire::test(Edit::class)
        ->set('job_card_id', $jobCard->id)
        ->set('priority_id', $high->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $order = VehicleInspectionOrder::firstOrFail();
    expect($order->order_no)->toBe('VIO-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT))
        ->and($order->priority->name)->toBe('HIGH')
        ->and($order->status)->toBe(VehicleInspectionOrder::STATUS_ASSIGNMENT_PENDING);
});

it('snapshots template items into the checklist when a template is picked', function () {
    $template = InspectionTemplateMaster::factory()->create(['is_active' => true]);
    $a = InspectionItemMaster::factory()->create(['name' => 'BRAKE PADS']);
    $b = InspectionItemMaster::factory()->create(['name' => 'OIL LEVEL']);
    $template->items()->attach([$a->id => ['position' => 1], $b->id => ['position' => 2]]);

    $component = Livewire::test(Edit::class)->set('inspection_template_id', $template->id);

    expect($component->get('items'))->toHaveCount(2)
        ->and($component->get('items')[0]['label'])->toBe('BRAKE PADS')
        ->and($component->get('items')[0]['result'])->toBe('pending');
});

it('saves checklist items with results and before/after photos', function () {
    Storage::fake('public');
    $order = VehicleInspectionOrder::factory()->create();

    Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])
        ->set('items', [
            ['id' => null, 'inspection_item_id' => null, 'inspection_item_group_id' => null, 'label' => 'brake pads', 'group_name' => null, 'result' => 'ia', 'notes' => 'worn', 'sequence_no' => 1, 'before_photo_path' => null, 'after_photo_path' => null],
            ['id' => null, 'inspection_item_id' => null, 'inspection_item_group_id' => null, 'label' => 'oil level', 'group_name' => null, 'result' => 'ok', 'notes' => null, 'sequence_no' => 2, 'before_photo_path' => null, 'after_photo_path' => null],
        ])
        ->set('itemBeforeFiles.0', UploadedFile::fake()->image('before.jpg'))
        ->set('itemAfterFiles.0', UploadedFile::fake()->image('after.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $order->refresh()->load('items');
    expect($order->items)->toHaveCount(2);

    $brake = $order->items->firstWhere('label', 'BRAKE PADS');
    expect($brake->result)->toBe('ia')
        ->and($brake->before_photo_path)->not->toBeNull()
        ->and($brake->after_photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($brake->before_photo_path);
    Storage::disk('public')->assertExists($brake->after_photo_path);
});

it('logs a work order started history event on the job card when status moves to wip', function () {
    $jobCard = JobCard::factory()->create();
    $order = VehicleInspectionOrder::factory()->create(['job_card_id' => $jobCard->id]);

    Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])
        ->set('status', VehicleInspectionOrder::STATUS_WIP)
        ->call('save')
        ->assertHasNoErrors();

    expect($order->fresh()->started_at)->not->toBeNull();

    $event = JobCardHistoryEvent::where('job_card_id', $jobCard->id)
        ->where('event_type', JobCardHistoryEvent::TYPE_WORK_ORDER_STARTED)
        ->first();
    expect($event)->not->toBeNull();
});

it('saves the pause/resume log', function () {
    $order = VehicleInspectionOrder::factory()->wip()->create();
    $reason = WorkOrderHoldReasonMaster::factory()->create(['name' => 'WAITING FOR PARTS']);

    Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])
        ->set('pauses', [
            ['id' => null, 'hold_reason_id' => $reason->id, 'paused_at' => '2026-06-21T10:00', 'resumed_at' => '2026-06-21T10:30', 'notes' => 'tea'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $order->refresh()->load('pauses');
    expect($order->pauses)->toHaveCount(1)
        ->and($order->pauses->first()->hold_reason_id)->toBe($reason->id)
        ->and($order->pauses->first()->notes)->toBe('TEA');
});

it('deletes a work order from the index', function () {
    $order = VehicleInspectionOrder::factory()->create();

    Livewire::test(Index::class)->call('delete', $order->id);

    expect(VehicleInspectionOrder::find($order->id))->toBeNull();
});
