<?php

use App\Models\User;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DigitalInspection\Livewire\Edit;
use App\Modules\DigitalInspection\Livewire\Index;
use App\Modules\DigitalInspection\Models\DigitalInspection;
use App\Modules\DigitalInspection\Models\DigitalInspectionItem;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\RecommendationCategoryMaster\Models\RecommendationCategoryMaster;
use App\Modules\RecommendationDescriptionMaster\Models\RecommendationDescriptionMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    DigitalInspection::factory()->count(3)->create();

    $this->get(route('digital-inspection.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('auto-stamps DI-00001 style inspection_no on create', function () {
    $row = DigitalInspection::factory()->create();

    expect($row->fresh()->inspection_no)->toBe('DI-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT));
});

it('seeds template items into the form when template is selected', function () {
    $template = InspectionTemplateMaster::factory()->create();
    $a = InspectionItemMaster::factory()->create(['name' => 'BRAKES']);
    $b = InspectionItemMaster::factory()->create(['name' => 'TYRE TREAD']);
    $c = InspectionItemMaster::factory()->create(['name' => 'BATTERY']);
    $template->items()->attach([$a->id, $b->id, $c->id]);

    Livewire::test(Edit::class)
        ->set('inspection_template_id', $template->id)
        ->assertCount('items', 3);
});

it('does not re-seed items when editing an existing inspection', function () {
    $template = InspectionTemplateMaster::factory()->create();
    $item = InspectionItemMaster::factory()->create();
    $template->items()->attach([$item->id]);

    $di = DigitalInspection::factory()->create(['inspection_template_id' => $template->id]);
    $di->items()->create(['inspection_item_id' => $item->id, 'outcome' => 'ok', 'notes' => 'EXISTING NOTE', 'sequence_no' => 1]);

    Livewire::test(Edit::class, ['digitalInspection' => $di])
        ->assertCount('items', 1)
        ->set('inspection_template_id', $template->id)  // attempt to re-seed
        ->assertCount('items', 1);                       // still 1
});

it('persists item outcomes and notes on save', function () {
    $jc = JobCard::factory()->create();
    $template = InspectionTemplateMaster::factory()->create();
    $a = InspectionItemMaster::factory()->create(['name' => 'BRAKES']);
    $b = InspectionItemMaster::factory()->create(['name' => 'TYRES']);
    $template->items()->attach([$a->id, $b->id]);

    Livewire::test(Edit::class)
        ->set('job_card_id', $jc->id)
        ->set('inspection_template_id', $template->id)
        ->set('items.0.outcome', 'rep')
        ->set('items.0.notes', 'pad worn through')
        ->set('items.1.outcome', 'ok')
        ->call('save')
        ->assertHasNoErrors();

    $di = DigitalInspection::with('items')->first();
    expect($di->items)->toHaveCount(2)
        ->and($di->items[0]->outcome)->toBe('rep')
        ->and($di->items[0]->notes)->toBe('PAD WORN THROUGH')
        ->and($di->items[1]->outcome)->toBe('ok')
        ->and($di->inspection_no)->toStartWith('DI-');
});

it('persists row-11 recommendation, severity and observation per item', function () {
    $jc = JobCard::factory()->create();
    $template = InspectionTemplateMaster::factory()->create();
    $a = InspectionItemMaster::factory()->create(['name' => 'BRAKES']);
    $template->items()->attach([$a->id]);

    Livewire::test(Edit::class)
        ->set('job_card_id', $jc->id)
        ->set('inspection_template_id', $template->id)
        ->set('items.0.outcome', 'poor')
        ->set('items.0.recommendation', 'urgent')
        ->set('items.0.severity', 'critical')
        ->set('items.0.observation', 'brake pads worn out')
        ->call('save')
        ->assertHasNoErrors();

    $di = DigitalInspection::with('items')->first();
    expect($di->items[0]->outcome)->toBe('poor')
        ->and($di->items[0]->recommendation)->toBe('urgent')
        ->and($di->items[0]->severity)->toBe('critical')
        ->and($di->items[0]->observation)->toBe('BRAKE PADS WORN OUT');
});

it('ignores an approved status typed at the form — the decision is not made here', function () {
    $di = DigitalInspection::factory()->create(['status' => DigitalInspection::STATUS_PENDING]);

    Livewire::test(Edit::class, ['digitalInspection' => $di])
        ->set('status', DigitalInspection::STATUS_APPROVED)
        ->call('save')
        ->assertHasNoErrors();

    expect($di->fresh()->status)->toBe(DigitalInspection::STATUS_PENDING);
});

it('stamps started_at once the first checklist item is answered', function () {
    $di = DigitalInspection::factory()->create([
        'status' => DigitalInspection::STATUS_PENDING,
        'started_at' => null,
    ]);
    $di->items()->create([
        'inspection_item_id' => InspectionItemMaster::factory()->create()->id,
        'outcome' => 'pending',
        'sequence_no' => 1,
    ]);
    $di->items()->create([
        'inspection_item_id' => InspectionItemMaster::factory()->create()->id,
        'outcome' => 'pending',
        'sequence_no' => 2,
    ]);

    Livewire::test(Edit::class, ['digitalInspection' => $di])
        ->set('items.0.outcome', 'ok')
        ->call('save')
        ->assertHasNoErrors();

    expect($di->fresh()->started_at)->not->toBeNull()
        ->and($di->fresh()->status)->toBe(DigitalInspection::STATUS_WIP);
});

it('stamps completed_at once every checklist item is answered', function () {
    $di = DigitalInspection::factory()->create(['status' => DigitalInspection::STATUS_PENDING]);
    $di->items()->create([
        'inspection_item_id' => InspectionItemMaster::factory()->create()->id,
        'outcome' => 'pending',
        'sequence_no' => 1,
    ]);

    Livewire::test(Edit::class, ['digitalInspection' => $di])
        ->set('items.0.outcome', 'ok')
        ->call('save')
        ->assertHasNoErrors();

    expect($di->fresh()->completed_at)->not->toBeNull()
        ->and($di->fresh()->status)->toBe(DigitalInspection::STATUS_COMPLETED);
});

it('filters by status and template', function () {
    $template = InspectionTemplateMaster::factory()->create();
    DigitalInspection::factory()->create();
    DigitalInspection::factory()->wip()->create();
    DigitalInspection::factory()->create(['inspection_template_id' => $template->id]);

    Livewire::test(Index::class)
        ->set('statusFilter', 'wip')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('statusFilter', 'all')
        ->set('templateFilter', (string) $template->id)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
});

it('Edit::save blocks a user without create permission', function () {
    $jc = JobCard::factory()->create();
    $template = InspectionTemplateMaster::factory()->create();

    $user = User::factory()->create();
    $user->givePermissionTo('digital_inspection.view');
    $this->actingAs($user);

    Livewire::test(Edit::class)
        ->set('job_card_id', $jc->id)
        ->set('inspection_template_id', $template->id)
        ->call('save')
        ->assertStatus(403);

    expect(DigitalInspection::count())->toBe(0);
});

it('deletes an inspection and cascades items', function () {
    $template = InspectionTemplateMaster::factory()->create();
    $item = InspectionItemMaster::factory()->create();
    $di = DigitalInspection::factory()->create(['inspection_template_id' => $template->id]);
    $di->items()->create(['inspection_item_id' => $item->id, 'outcome' => 'ok', 'sequence_no' => 1]);

    Livewire::test(Index::class)->call('delete', $di->id);

    expect(DigitalInspection::find($di->id))->toBeNull()
        ->and(DigitalInspectionItem::where('digital_inspection_id', $di->id)->count())->toBe(0);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('digital-inspection.index'))->assertRedirect(route('login'));
});

it('saves a per-item evidence image and stamps image_path on the row', function () {
    Storage::fake('public');

    $template = InspectionTemplateMaster::factory()->create();
    $item = InspectionItemMaster::factory()->create(['name' => 'BRAKES']);
    $template->items()->attach([$item->id]);
    $jobCard = JobCard::factory()->create();

    Livewire::test(Edit::class)
        ->set('job_card_id', $jobCard->id)
        ->set('inspection_template_id', $template->id)  // seeds $items
        ->set("itemImages.{$item->id}", UploadedFile::fake()->image('brakes-worn.jpg', 800, 600))
        ->set('items.0.outcome', 'rep')
        ->call('save')
        ->assertHasNoErrors();

    $persistedItem = DigitalInspectionItem::query()->first();
    expect($persistedItem->image_path)->not->toBeNull();
    expect($persistedItem->image_path)->toStartWith('digital-inspections/');
    Storage::disk('public')->assertExists($persistedItem->image_path);
});

it('removes a saved item image and deletes the file when removeItemImage + save', function () {
    Storage::fake('public');

    $template = InspectionTemplateMaster::factory()->create();
    $item = InspectionItemMaster::factory()->create();
    $template->items()->attach([$item->id]);

    $di = DigitalInspection::factory()->create(['inspection_template_id' => $template->id]);
    $stored = UploadedFile::fake()->image('old.jpg')->store("digital-inspections/{$di->id}/items", 'public');
    $diItem = $di->items()->create([
        'inspection_item_id' => $item->id,
        'outcome' => 'ok',
        'sequence_no' => 1,
        'image_path' => $stored,
    ]);

    Livewire::test(Edit::class, ['digitalInspection' => $di])
        ->call('removeItemImage', $item->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($diItem->fresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($stored);
});

it('replaces a saved item image and deletes the old file', function () {
    Storage::fake('public');

    $template = InspectionTemplateMaster::factory()->create();
    $item = InspectionItemMaster::factory()->create();
    $template->items()->attach([$item->id]);

    $di = DigitalInspection::factory()->create(['inspection_template_id' => $template->id]);
    $oldPath = UploadedFile::fake()->image('old.jpg')->store("digital-inspections/{$di->id}/items", 'public');
    $diItem = $di->items()->create([
        'inspection_item_id' => $item->id,
        'outcome' => 'ok',
        'sequence_no' => 1,
        'image_path' => $oldPath,
    ]);

    Livewire::test(Edit::class, ['digitalInspection' => $di])
        ->set("itemImages.{$item->id}", UploadedFile::fake()->image('new.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $newPath = $diItem->fresh()->image_path;
    expect($newPath)->not->toBe($oldPath)
        ->and($newPath)->not->toBeNull();
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($newPath);
});

it('rejects an oversized item image', function () {
    Storage::fake('public');

    $template = InspectionTemplateMaster::factory()->create();
    $item = InspectionItemMaster::factory()->create();
    $template->items()->attach([$item->id]);
    $jobCard = JobCard::factory()->create();

    Livewire::test(Edit::class)
        ->set('job_card_id', $jobCard->id)
        ->set('inspection_template_id', $template->id)
        ->set("itemImages.{$item->id}", UploadedFile::fake()->image('huge.jpg')->size(9000))  // 9 MB > 8 MB cap
        ->call('save')
        ->assertHasErrors(['itemImages.'.$item->id]);

    expect(DigitalInspection::count())->toBe(0);
});

it('picks the recommendation description from the master instead of typing it', function () {
    $category = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    RecommendationDescriptionMaster::factory()->create(['name' => 'OIL LEAKAGE', 'category_id' => $category->id]);
    $template = InspectionTemplateMaster::factory()->create(['is_active' => true]);
    $template->items()->attach([InspectionItemMaster::factory()->create()->id => ['position' => 1]]);

    Livewire::test(Edit::class)
        ->set('inspection_template_id', $template->id)
        // The free-text observation box is gone; the wording comes off the master.
        ->assertDontSee('di-standard-observations', false)
        ->assertSee('Recommendation Desc')
        ->assertSee('OIL LEAKAGE');
});

// ---------------------------------------------------------------------------
// Inspection Setup: what the form offers, and what it works out for itself
// ---------------------------------------------------------------------------

it('offers only job cards still in the workshop', function () {
    $pending = JobCard::factory()->create(['status' => JobCard::STATUS_IN_PROGRESS]);
    $closed = JobCard::factory()->create(['status' => JobCard::STATUS_CLOSED]);

    $offered = Livewire::test(Edit::class)->instance()->jobCards->modelKeys();

    expect($offered)->toContain($pending->id)
        ->not->toContain($closed->id);
});

it('offers only technicians in the technician picker', function () {
    $tech = EmployeeMaster::factory()->technician()->create(['is_active' => true]);
    $advisor = EmployeeMaster::factory()->advisor()->create(['is_active' => true]);

    $offered = Livewire::test(Edit::class)->instance()->technicians->modelKeys();

    expect($offered)->toContain($tech->id)->not->toContain($advisor->id);
});

it('offers only floor in-charges in the floor in-charge picker', function () {
    $floor = EmployeeMaster::factory()->floorIncharge()->create(['is_active' => true]);
    $tech = EmployeeMaster::factory()->technician()->create(['is_active' => true]);

    $offered = Livewire::test(Edit::class)->instance()->floorIncharges->modelKeys();

    expect($offered)->toContain($floor->id)->not->toContain($tech->id);
});

it('pulls advisor, department, service type and vehicle detail off the picked job card', function () {
    $advisor = EmployeeMaster::factory()->advisor()->create(['is_active' => true]);
    $dept = WorkshopDepartmentMaster::factory()->create(['name' => 'MECHANICAL']);
    $serviceType = ServiceTypeMaster::factory()->create(['name' => 'PAID SERVICE', 'is_active' => true]);
    $variant = VehicleVariantMaster::factory()->create(['name' => '1.2 VXI']);
    $vehicle = CustomerVehicleMaster::factory()->create([
        'variant_id' => $variant->id,
        'year_of_manufacture' => 2021,
        'odometer_km' => 42000,
    ]);
    $jobCard = JobCard::factory()->create([
        'customer_vehicle_id' => $vehicle->id,
        'assigned_advisor_id' => $advisor->id,
        'workshop_department_id' => $dept->id,
        'service_type_id' => $serviceType->id,
        'status' => JobCard::STATUS_OPEN,
    ]);

    $context = Livewire::test(Edit::class)
        ->set('job_card_id', $jobCard->id)
        ->instance()
        ->jobCardContext;

    expect($context->advisor->name)->toBe($advisor->name)
        ->and($context->workshopDepartment->name)->toBe('MECHANICAL')
        ->and($context->serviceType->name)->toBe('PAID SERVICE')
        ->and($context->customerVehicle->variant->name)->toBe('1.2 VXI')
        ->and($context->customerVehicle->year_of_manufacture)->toBe(2021)
        ->and((int) $context->customerVehicle->odometer_km)->toBe(42000);
});

it('no longer offers approved or rejected as statuses', function () {
    expect(array_keys(DigitalInspection::statuses()))
        ->not->toContain(DigitalInspection::STATUS_APPROVED)
        ->not->toContain(DigitalInspection::STATUS_REJECTED)
        // Historic rows must still render their own name.
        ->and(DigitalInspection::allStatuses())
        ->toHaveKey(DigitalInspection::STATUS_APPROVED);
});

it('works the status out from the checklist rather than taking it from the form', function () {
    $template = InspectionTemplateMaster::factory()->create(['is_active' => true]);
    $a = InspectionItemMaster::factory()->create();
    $b = InspectionItemMaster::factory()->create();
    $template->items()->attach([$a->id => ['position' => 1], $b->id => ['position' => 2]]);

    $component = Livewire::test(Edit::class)
        ->set('job_card_id', JobCard::factory()->create(['status' => JobCard::STATUS_OPEN])->id)
        ->set('inspection_template_id', $template->id)
        // Typed status is ignored: the sheet decides.
        ->set('status', DigitalInspection::STATUS_COMPLETED)
        ->call('save')
        ->assertHasNoErrors();

    $di = DigitalInspection::firstOrFail();
    expect($di->status)->toBe(DigitalInspection::STATUS_PENDING)
        ->and($di->completed_at)->toBeNull();

    // One answered of two: in progress, and the start is stamped.
    $component->set('items.0.outcome', 'ok')->call('save')->assertHasNoErrors();
    $di->refresh();
    expect($di->status)->toBe(DigitalInspection::STATUS_WIP)
        ->and($di->started_at)->not->toBeNull()
        ->and($di->completed_at)->toBeNull();

    // Both answered: completed.
    $component->set('items.1.outcome', 'ok')->call('save')->assertHasNoErrors();
    $di->refresh();
    expect($di->status)->toBe(DigitalInspection::STATUS_COMPLETED)
        ->and($di->completed_at)->not->toBeNull();

    // Reopened: the completion stamp must not outlive the completion.
    $component->set('items.1.outcome', 'pending')->call('save')->assertHasNoErrors();
    $di->refresh();
    expect($di->status)->toBe(DigitalInspection::STATUS_WIP)
        ->and($di->completed_at)->toBeNull();
});

// ---------------------------------------------------------------------------
// Checklist: action type, recommendation, severity
// ---------------------------------------------------------------------------

it('offers only pending, IA, FA and NA as action types', function () {
    expect(array_keys(DigitalInspection::outcomes()))
        ->toBe(['pending', 'ia', 'fa', 'na'])
        // Historic rows keep their own wording.
        ->and(DigitalInspection::allOutcomes())->toHaveKeys(['ok', 'faulty', 'not_checked']);
});

it('offers skimming and drops no-action and urgent from recommendations', function () {
    expect(array_keys(DigitalInspection::recommendations()))
        ->toBe(['repair', 'replace', 'skimming', 'monitor'])
        ->and(DigitalInspection::allRecommendations())->toHaveKeys(['none', 'urgent']);
});

it('fills severity in from the action type, and lets the technician move it', function () {
    $component = inspectionWithOneItem();

    $component->set('items.0.outcome', DigitalInspection::ACTION_IMMEDIATE);
    expect($component->get('items.0.severity'))->toBe('high');

    $component->set('items.0.outcome', DigitalInspection::ACTION_FUTURE);
    expect($component->get('items.0.severity'))->toBe('low');

    // A deliberate choice survives a later action-type change.
    $component->set('items.0.severity', 'critical')
        ->set('items.0.outcome', DigitalInspection::ACTION_IMMEDIATE);
    expect($component->get('items.0.severity'))->toBe('critical');
});

it('clears recommendation and severity when a checkpoint needs no attention', function () {
    $component = inspectionWithOneItem()
        ->set('items.0.outcome', DigitalInspection::ACTION_IMMEDIATE)
        ->set('items.0.recommendation', 'repair');

    expect($component->get('items.0.severity'))->toBe('high');

    $component->set('items.0.outcome', DigitalInspection::ACTION_NONE);

    expect($component->get('items.0.recommendation'))->toBeNull()
        ->and($component->get('items.0.severity'))->toBeNull();
});

it('will not persist a recommendation or severity smuggled onto a no-attention row', function () {
    $component = inspectionWithOneItem()
        ->set('items.0.outcome', DigitalInspection::ACTION_NONE)
        // Set directly, bypassing the hook, the way a stale resubmit would.
        ->set('items.0.recommendation', 'repair')
        ->set('items.0.severity', 'critical')
        ->call('save')
        ->assertHasNoErrors();

    $item = DigitalInspection::firstOrFail()->items()->firstOrFail();

    expect($item->outcome)->toBe(DigitalInspection::ACTION_NONE)
        ->and($item->recommendation)->toBeNull()
        ->and($item->severity)->toBeNull();
});

it('captures technician TAT from the checklist stamps', function () {
    $tech = EmployeeMaster::factory()->technician()->create(['is_active' => true]);
    $component = inspectionWithOneItem()->set('assigned_technician_id', $tech->id);

    expect($component->instance()->turnaround)->toBeNull();

    $component->set('items.0.outcome', DigitalInspection::ACTION_FUTURE)
        ->call('save')
        ->assertHasNoErrors();

    $di = DigitalInspection::firstOrFail();

    expect($di->assigned_technician_id)->toBe($tech->id)
        ->and($di->started_at)->not->toBeNull()
        ->and($di->completed_at)->not->toBeNull()
        ->and($component->instance()->turnaround)->not->toBeNull()
        ->and($component->instance()->assignedTechnicianName)->toBe($tech->name);
});

/** A saved inspection carrying exactly one checklist item, ready to answer. */
function inspectionWithOneItem(): Testable
{
    $template = InspectionTemplateMaster::factory()->create(['is_active' => true]);
    $template->items()->attach([InspectionItemMaster::factory()->create()->id => ['position' => 1]]);

    return Livewire::test(Edit::class)
        ->set('job_card_id', JobCard::factory()->create(['status' => JobCard::STATUS_OPEN])->id)
        ->set('inspection_template_id', $template->id);
}

// ---------------------------------------------------------------------------
// Ordering, customer approval and internal control
// ---------------------------------------------------------------------------

it('walks the checklist in the order the masters set, not alphabetically', function () {
    $late = InspectionItemGroupMaster::factory()->create(['name' => 'ZZ LAST', 'is_active' => true, 'sequence_no' => 1]);
    $early = InspectionItemGroupMaster::factory()->create(['name' => 'AA FIRST', 'is_active' => true, 'sequence_no' => 2]);
    $zItem = InspectionItemMaster::factory()->create(['name' => 'ZZ ITEM', 'inspection_item_group_id' => $late->id, 'sequence_no' => 1]);
    $aItem = InspectionItemMaster::factory()->create(['name' => 'AA ITEM', 'inspection_item_group_id' => $early->id, 'sequence_no' => 1]);

    $template = InspectionTemplateMaster::factory()->create(['is_active' => true]);
    // Attached in the opposite order, so only the masters' sequence can produce this.
    $template->items()->attach([$aItem->id => ['position' => 1], $zItem->id => ['position' => 2]]);

    $items = Livewire::test(Edit::class)
        ->set('inspection_template_id', $template->id)
        ->get('items');

    expect(collect($items)->pluck('name')->all())->toBe(['ZZ ITEM', 'AA ITEM']);
});

it('records what the customer was shown and what they decided', function () {
    $di = DigitalInspection::factory()->create();

    Livewire::test(Edit::class, ['digitalInspection' => $di])
        ->set('explained_on_lift', true)
        ->set('media_shared', true)
        ->set('questions_answered', true)
        ->set('customer_approval', 'deferred')
        ->call('save')
        ->assertHasNoErrors();

    $di->refresh();

    expect($di->explained_on_lift)->toBeTrue()
        ->and($di->media_shared)->toBeTrue()
        ->and($di->questions_answered)->toBeTrue()
        ->and($di->customer_approval)->toBe('deferred')
        ->and($di->customer_approval_at)->not->toBeNull();
});

it('stamps a sign-off when the name goes on, and clears it when the name comes off', function () {
    $di = DigitalInspection::factory()->create();
    $tech = EmployeeMaster::factory()->technician()->create(['is_active' => true]);

    $component = Livewire::test(Edit::class, ['digitalInspection' => $di])
        ->set('technician_signed_by_id', $tech->id)
        ->call('save')
        ->assertHasNoErrors();

    $di->refresh();
    $stamped = $di->technician_signed_at;

    expect($stamped)->not->toBeNull()
        ->and($di->technician_signed_by_id)->toBe($tech->id)
        // The other two rows stay unsigned.
        ->and($di->supervisor_signed_at)->toBeNull()
        ->and($di->advisor_signed_at)->toBeNull();

    // Saving again must not move the moment somebody put their name to it.
    $component->call('save');
    expect($di->fresh()->technician_signed_at->equalTo($stamped))->toBeTrue();

    $component->set('technician_signed_by_id', null)->call('save');
    expect($di->fresh()->technician_signed_at)->toBeNull();
});

it('rejects a customer approval it does not recognise', function () {
    $di = DigitalInspection::factory()->create();

    Livewire::test(Edit::class, ['digitalInspection' => $di])
        ->set('customer_approval', 'maybe')
        ->call('save')
        ->assertHasErrors('customer_approval');
});

// ---------------------------------------------------------------------------
// Recommendation descriptions: filed by category, picked several at a time
// ---------------------------------------------------------------------------

it('narrows the recommendation list to the row\'s category', function () {
    $brakes = RecommendationCategoryMaster::factory()->create(['name' => 'BRAKES', 'is_active' => true]);
    $engine = RecommendationCategoryMaster::factory()->create(['name' => 'ENGINE', 'is_active' => true]);
    $pads = RecommendationDescriptionMaster::factory()->create(['name' => 'REPLACE PADS', 'category_id' => $brakes->id]);
    $oil = RecommendationDescriptionMaster::factory()->create(['name' => 'TOP UP OIL', 'category_id' => $engine->id]);

    $component = inspectionWithOneItem();

    expect($component->instance()->recommendationOptions(0)->modelKeys())
        ->toContain($pads->id)->toContain($oil->id);

    $component->set('itemRecCategory.0', $brakes->id);

    expect($component->instance()->recommendationOptions(0)->modelKeys())
        ->toContain($pads->id)->not->toContain($oil->id);
});

it('keeps a chosen description on offer even after the category narrows past it', function () {
    $brakes = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $engine = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $oil = RecommendationDescriptionMaster::factory()->create(['category_id' => $engine->id]);

    $component = inspectionWithOneItem()
        ->set('itemRecommendations.0', [$oil->id])
        ->set('itemRecCategory.0', $brakes->id);

    // Otherwise the row would render blank for a value it actually holds.
    expect($component->instance()->recommendationOptions(0)->modelKeys())->toContain($oil->id);
});

it('ticks every offered description at once, and clears them again', function () {
    $category = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $a = RecommendationDescriptionMaster::factory()->create(['category_id' => $category->id]);
    $b = RecommendationDescriptionMaster::factory()->create(['category_id' => $category->id]);

    $component = inspectionWithOneItem()
        ->set('itemRecCategory.0', $category->id)
        ->call('selectAllRecommendations', 0);

    expect($component->get('itemRecommendations.0'))->toEqualCanonicalizing([$a->id, $b->id]);

    $component->call('clearRecommendations', 0);
    expect($component->get('itemRecommendations.0'))->toBe([]);
});

it('saves several recommendations against one checkpoint and reads them back', function () {
    $category = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $a = RecommendationDescriptionMaster::factory()->create(['category_id' => $category->id]);
    $b = RecommendationDescriptionMaster::factory()->create(['category_id' => $category->id]);

    inspectionWithOneItem()
        ->set('items.0.outcome', DigitalInspection::ACTION_IMMEDIATE)
        ->set('itemRecommendations.0', [$a->id, $b->id])
        ->call('save')
        ->assertHasNoErrors();

    $item = DigitalInspection::firstOrFail()->items()->firstOrFail();
    expect($item->recommendationDescriptions->modelKeys())->toEqualCanonicalizing([$a->id, $b->id]);

    // And they come back onto the form when it is reopened.
    $reopened = Livewire::test(Edit::class, ['digitalInspection' => DigitalInspection::firstOrFail()]);
    expect($reopened->get('itemRecommendations.0'))->toEqualCanonicalizing([$a->id, $b->id]);
});

it('drops the recommendations when a checkpoint turns out to need no attention', function () {
    $category = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $a = RecommendationDescriptionMaster::factory()->create(['category_id' => $category->id]);

    inspectionWithOneItem()
        ->set('items.0.outcome', DigitalInspection::ACTION_IMMEDIATE)
        ->set('itemRecommendations.0', [$a->id])
        ->call('save')
        ->assertHasNoErrors();

    $item = DigitalInspection::firstOrFail()->items()->firstOrFail();
    expect($item->recommendationDescriptions)->toHaveCount(1);

    Livewire::test(Edit::class, ['digitalInspection' => DigitalInspection::firstOrFail()])
        ->set('items.0.outcome', DigitalInspection::ACTION_NONE)
        ->call('save')
        ->assertHasNoErrors();

    expect($item->fresh()->recommendationDescriptions)->toHaveCount(0);
});

it('quick-adds wording to the master and ticks it on the row', function () {
    $category = RecommendationCategoryMaster::factory()->create(['is_active' => true]);

    $component = inspectionWithOneItem()
        ->call('openRecommendationQuickAdd', 0)
        ->set('quickRecCategoryId', $category->id)
        ->set('quickRecName', 'change air filter')
        ->call('createRecommendationDescription')
        ->assertHasNoErrors();

    $created = RecommendationDescriptionMaster::where('name', 'CHANGE AIR FILTER')->firstOrFail();

    expect($created->category_id)->toBe($category->id)
        ->and($created->is_active)->toBeTrue()
        ->and($component->get('itemRecommendations.0'))->toContain($created->id);
});

it('refuses a quick-add sub category that belongs to a different category', function () {
    $brakes = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $engine = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $front = RecommendationCategoryMaster::factory()->under($brakes)->create(['is_active' => true]);

    inspectionWithOneItem()
        ->call('openRecommendationQuickAdd', 0)
        ->set('quickRecCategoryId', $engine->id)
        ->set('quickRecSubCategoryId', $front->id)
        ->set('quickRecName', 'something')
        ->call('createRecommendationDescription')
        ->assertHasErrors('quickRecSubCategoryId');
});
