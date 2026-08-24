<?php

use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DocumentCollection\Livewire\Edit;
use App\Modules\DocumentCollection\Livewire\Index;
use App\Modules\DocumentCollection\Models\DocumentCollection;
use App\Modules\DocumentRejectionReasonMaster\Models\DocumentRejectionReasonMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

function dcTemplate(string $name = 'DOC SET'): ChecklistTemplateMaster
{
    return ChecklistTemplateMaster::create([
        'name' => $name,
        'applies_to' => 'claim',
        'items' => [
            ['label' => 'RC BOOK', 'is_required' => true],
            ['label' => 'INSURANCE POLICY', 'is_required' => true],
        ],
        'is_active' => true,
    ]);
}

/** The full mandatory set the form now enforces. */
function validDC(Testable $c): Testable
{
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $service = ServiceTypeMaster::factory()->create(['workshop_department_id' => $dept->id]);

    return $c
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('department_id', $dept->id)
        ->set('service_type_id', $service->id)
        ->set('created_by_advisor_id', EmployeeMaster::factory()->create()->id)
        ->set('purpose', array_key_first(DocumentCollection::purposes()))
        ->set('checklist_template_id', dcTemplate()->id)
        ->set('verification_template_id', dcTemplate('VERIFY SET')->id);
}

it('renders the index page', function () {
    DocumentCollection::factory()->count(2)->create();

    $this->get(route('document-collection.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('document-collection.index'))->assertRedirect(route('login'));
});

it('creates a collection, stamps DC number, and redirects into the editor', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    validDC(Livewire::test(Edit::class))
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('request_type', 'insurance_claim')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $dc = DocumentCollection::firstOrFail();
    expect($dc->doc_collection_no)->toBe('DC-'.str_pad((string) $dc->id, 5, '0', STR_PAD_LEFT))
        ->and($dc->request_type)->toBe('insurance_claim')
        ->and($dc->entry_at)->not->toBeNull();
});

it('snapshots checklist template items when a template is picked', function () {
    $template = dcTemplate();

    $component = Livewire::test(Edit::class)->set('checklist_template_id', $template->id);

    expect($component->get('items'))->toHaveCount(2)
        ->and($component->get('items')[0]['label'])->toBe('RC BOOK')
        ->and($component->get('items')[0]['is_required'])->toBeTrue();
});

it('saves checklist items with status, attachment and a rejection reason', function () {
    Storage::fake('public');
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $reason = DocumentRejectionReasonMaster::factory()->create(['name' => 'EXPIRED DOCUMENT']);
    $dc = DocumentCollection::factory()->create(['customer_id' => $customer->id, 'customer_vehicle_id' => $vehicle->id]);

    Livewire::test(Edit::class, ['documentCollection' => $dc])
        ->set('items', [
            ['id' => null, 'label' => 'rc book', 'is_required' => true, 'status' => 'received', 'rejection_reason_id' => null, 'notes' => null, 'path' => null, 'original_name' => null],
            ['id' => null, 'label' => 'pan', 'is_required' => false, 'status' => 'rejected', 'rejection_reason_id' => $reason->id, 'notes' => 'blurred', 'path' => null, 'original_name' => null],
        ])
        ->set('itemFiles.0', UploadedFile::fake()->create('rc.pdf', 200, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors();

    $dc->refresh()->load('items');
    expect($dc->items)->toHaveCount(2);

    $rc = $dc->items->firstWhere('label', 'RC BOOK');
    expect($rc->status)->toBe('received')->and($rc->path)->not->toBeNull();
    Storage::disk('public')->assertExists($rc->path);

    $pan = $dc->items->firstWhere('label', 'PAN');
    expect($pan->status)->toBe('rejected')->and($pan->rejection_reason_id)->toBe($reason->id);
    expect($dc->fresh()->uploaded_at)->not->toBeNull();
});

it('saves the verification checklist', function () {
    $dc = DocumentCollection::factory()->create();

    Livewire::test(Edit::class, ['documentCollection' => $dc])
        ->set('verifications', [
            ['id' => null, 'label' => 'name match', 'is_verified' => true, 'notes' => null],
            ['id' => null, 'label' => 'policy validity', 'is_verified' => false, 'notes' => 'expired'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $dc->refresh()->load('verifications');
    expect($dc->verifications)->toHaveCount(2)
        ->and($dc->verifications->firstWhere('label', 'NAME MATCH')->is_verified)->toBeTrue();
});

it('deletes a collection from the index', function () {
    $dc = DocumentCollection::factory()->create();

    Livewire::test(Index::class)->call('delete', $dc->id);

    expect(DocumentCollection::find($dc->id))->toBeNull();
});

it('accepts a DOCX attachment as the CSV requires', function () {
    Storage::fake('public');
    $dc = DocumentCollection::factory()->create();

    Livewire::test(Edit::class, ['documentCollection' => $dc])
        ->set('items', [
            ['id' => null, 'label' => 'RC BOOK', 'is_required' => true, 'status' => 'pending',
                'rejection_reason_id' => null, 'path' => null, 'original_name' => null,
                'mime_type' => null, 'size_bytes' => null, 'notes' => null, 'sequence_no' => 1],
        ])
        ->set('itemFiles.0', UploadedFile::fake()->create('claim-form.docx', 40, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))
        ->call('save')
        ->assertHasNoErrors(['itemFiles.0']);
});

it('requires a custom interval only when the reminder is Custom', function () {
    $dc = DocumentCollection::factory()->create();

    Livewire::test(Edit::class, ['documentCollection' => $dc])
        ->set('reminder_frequency', DocumentCollection::REMINDER_CUSTOM)
        ->set('reminder_custom_days', null)
        ->call('save')
        ->assertHasErrors(['reminder_custom_days']);

    Livewire::test(Edit::class, ['documentCollection' => $dc])
        ->set('reminder_frequency', 'daily')
        ->call('save')
        ->assertHasNoErrors(['reminder_custom_days']);
});

it('requires a day count only when retention is Delete', function () {
    $dc = DocumentCollection::factory()->create();

    Livewire::test(Edit::class, ['documentCollection' => $dc])
        ->set('retention', DocumentCollection::RETENTION_DELETE)
        ->set('retention_days', null)
        ->call('save')
        ->assertHasErrors(['retention_days']);
});

it('computes the retention due date from the job card closed_at', function () {
    $jobCard = JobCard::factory()->create(['closed_at' => now()->subDays(100)]);

    $dc = DocumentCollection::factory()->create([
        'job_card_id' => $jobCard->id,
        'retention' => DocumentCollection::RETENTION_DELETE,
        'retention_days' => 90,
    ]);

    expect($dc->retentionDueAt()->toDateString())->toBe(now()->subDays(10)->toDateString())
        ->and($dc->isRetentionDue())->toBeTrue();

    // Not yet elapsed.
    $fresh = DocumentCollection::factory()->create([
        'job_card_id' => JobCard::factory()->create(['closed_at' => now()])->id,
        'retention' => DocumentCollection::RETENTION_DELETE,
        'retention_days' => 90,
    ]);
    expect($fresh->isRetentionDue())->toBeFalse();

    // No job card means no clock at all.
    $unbilled = DocumentCollection::factory()->create([
        'job_card_id' => null,
        'retention' => DocumentCollection::RETENTION_DELETE,
        'retention_days' => 1,
    ]);
    expect($unbilled->retentionDueAt())->toBeNull()
        ->and($unbilled->isRetentionDue())->toBeFalse();
});

it('retires due collections via the scheduled command, cascading to items', function () {
    $jobCard = JobCard::factory()->create(['closed_at' => now()->subDays(200)]);
    $due = DocumentCollection::factory()->create([
        'job_card_id' => $jobCard->id,
        'retention' => DocumentCollection::RETENTION_DELETE,
        'retention_days' => 30,
    ]);
    $due->items()->create(['label' => 'RC BOOK', 'sequence_no' => 1]);

    $keep = DocumentCollection::factory()->create(['retention' => DocumentCollection::RETENTION_ACTIVE]);

    // Dry run changes nothing.
    $this->artisan('documents:apply-retention', ['--dry-run' => true])->assertSuccessful();
    expect(DocumentCollection::find($due->id))->not->toBeNull();

    $this->artisan('documents:apply-retention')->assertSuccessful();

    expect(DocumentCollection::find($due->id))->toBeNull()
        ->and(DocumentCollection::withTrashed()->find($due->id)->retired_at)->not->toBeNull()
        ->and(DocumentCollection::find($keep->id))->not->toBeNull();

    // Items went with it, and come back on restore.
    $trashed = DocumentCollection::withTrashed()->find($due->id);
    expect($trashed->items()->count())->toBe(0);

    $trashed->restore();
    expect($trashed->items()->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Stamps & derived status (rework chunk 2)
|--------------------------------------------------------------------------
*/

it('stamps requested_at on create and derives requested status', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    validDC(Livewire::test(Edit::class))
        ->call('save')
        ->assertHasNoErrors();

    $dc = DocumentCollection::firstOrFail();
    expect($dc->requested_at)->not->toBeNull()
        ->and($dc->status)->toBe(DocumentCollection::STATUS_REQUESTED);
});

it('stamps each document as it is received, and completes when all required are in', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    // validDC's template snapshots RC BOOK + INSURANCE POLICY as items 0 and 1.
    $component = validDC(Livewire::test(Edit::class))
        ->call('save')
        ->assertHasNoErrors();

    $dc = DocumentCollection::firstOrFail();
    $dc->items()->update(['is_required' => true]);

    // First document lands: its own stamp, header still open.
    Livewire::test(Edit::class, ['documentCollection' => $dc->fresh()])
        ->set('items.0.status', 'received')
        ->call('save')
        ->assertHasNoErrors();

    $first = $dc->items()->orderBy('sequence_no')->first();
    expect($first->received_at)->not->toBeNull()
        ->and($dc->fresh()->status)->toBe(DocumentCollection::STATUS_REQUESTED)
        ->and($dc->fresh()->received_at)->toBeNull();

    // Second lands days apart — header completes with the LAST arrival.
    Livewire::test(Edit::class, ['documentCollection' => $dc->fresh()])
        ->set('items.1.status', 'received')
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $dc->fresh();
    expect($fresh->status)->toBe(DocumentCollection::STATUS_RECEIVED)
        ->and($fresh->received_at)->not->toBeNull();
});

it('keeps an already-received document\'s original stamp on later saves', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    validDC(Livewire::test(Edit::class))
        ->set('items.0.status', 'received')
        ->call('save')
        ->assertHasNoErrors();

    $dc = DocumentCollection::firstOrFail();
    $original = $dc->items()->first()->received_at;
    $dc->items()->first()->forceFill(['received_at' => now()->subDays(3)])->save();

    Livewire::test(Edit::class, ['documentCollection' => $dc->fresh()])
        ->set('notes', 'touched')
        ->call('save')
        ->assertHasNoErrors();

    expect($dc->items()->first()->received_at->isSameDay(now()->subDays(3)))->toBeTrue();
});

it('no longer offers a status dropdown', function () {
    expect(Livewire::test(Edit::class)->html())->not->toContain('wire:model="status"');
});

/*
|--------------------------------------------------------------------------
| Follow-up log & request message (rework chunk 3)
|--------------------------------------------------------------------------
*/

it('records multiple follow-ups with who, mode, and the customer response', function () {
    $mode = FollowUpModeMaster::factory()->create(['name' => 'WHATSAPP']);
    $by = EmployeeMaster::factory()->create();

    validDC(Livewire::test(Edit::class))
        ->call('addFollowUp')
        ->set('followUps.0.followed_up_by_id', $by->id)
        ->set('followUps.0.follow_up_mode_id', $mode->id)
        ->set('followUps.0.customer_response', 'will send by friday')
        ->call('addFollowUp')
        ->set('followUps.1.customer_response', 'not reachable')
        ->call('save')
        ->assertHasNoErrors();

    $dc = DocumentCollection::firstOrFail();
    expect($dc->followUps)->toHaveCount(2)
        ->and($dc->followUps->pluck('customer_response'))->toContain('WILL SEND BY FRIDAY', 'NOT REACHABLE')
        ->and($dc->followUps->firstWhere('customer_response', 'WILL SEND BY FRIDAY')->followed_up_by_id)->toBe($by->id);
});

it('composes the request message from the outstanding documents only', function () {
    $component = validDC(Livewire::test(Edit::class))
        ->set('items.0.status', 'received');

    $message = $component->instance()->requestMessage;

    expect($message)->toContain('INSURANCE POLICY')
        ->and($message)->not->toContain('1. RC BOOK');
});

it('enforces the mandatory set', function () {
    Livewire::test(Edit::class)
        ->call('save')
        ->assertHasErrors([
            'customer_id', 'customer_vehicle_id', 'purpose',
            'department_id', 'service_type_id', 'created_by_advisor_id',
            'checklist_template_id', 'verification_template_id',
        ]);
});

/*
|--------------------------------------------------------------------------
| History listing (rework chunk 4)
|--------------------------------------------------------------------------
*/

it('defaults the history to open collections', function () {
    DocumentCollection::factory()->create(['status' => 'requested']);
    DocumentCollection::factory()->create(['status' => 'received']);

    $component = Livewire::test(Index::class);

    expect($component->get('statusFilter'))->toBe('open');
    $component->assertViewHas('rows', fn ($rows) => $rows->total() === 1)
        ->set('statusFilter', 'all')
        ->assertViewHas('rows', fn ($rows) => $rows->total() === 2);
});

it('filters by department, created by, collected by, and requested date', function () {
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();
    DocumentCollection::factory()->create([
        'department_id' => $dept->id,
        'created_by_advisor_id' => $advisor->id,
        'requested_at' => '2026-08-01 10:00:00',
    ]);
    DocumentCollection::factory()->create(['requested_at' => '2026-08-20 10:00:00']);

    Livewire::test(Index::class)
        ->set('statusFilter', 'all')
        ->set('deptFilter', (string) $dept->id)
        ->assertViewHas('rows', fn ($rows) => $rows->total() === 1)
        ->set('deptFilter', 'all')
        ->set('createdByFilter', (string) $advisor->id)
        ->assertViewHas('rows', fn ($rows) => $rows->total() === 1)
        ->set('createdByFilter', 'all')
        ->set('dateFrom', '2026-08-15')
        ->assertViewHas('rows', fn ($rows) => $rows->total() === 1);
});

it('searches by the linked job card number', function () {
    $jobCard = JobCard::factory()->create();
    DocumentCollection::factory()->create(['job_card_id' => $jobCard->id]);
    DocumentCollection::factory()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', 'all')
        ->set('search', $jobCard->job_card_no)
        ->assertViewHas('rows', fn ($rows) => $rows->total() === 1);
});

it('sorts by a related column via subquery', function () {
    $a = EmployeeMaster::factory()->create(['name' => 'AAA ADVISOR']);
    $z = EmployeeMaster::factory()->create(['name' => 'ZZZ ADVISOR']);
    DocumentCollection::factory()->create(['created_by_advisor_id' => $z->id]);
    DocumentCollection::factory()->create(['created_by_advisor_id' => $a->id]);

    Livewire::test(Index::class)
        ->set('statusFilter', 'all')
        ->call('sort', 'created_by')
        ->assertViewHas('rows', fn ($rows) => $rows->first()->advisor->name === 'AAA ADVISOR');
});
