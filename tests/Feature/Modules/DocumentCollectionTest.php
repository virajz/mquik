<?php

use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DocumentCollection\Livewire\Edit;
use App\Modules\DocumentCollection\Livewire\Index;
use App\Modules\DocumentCollection\Models\DocumentCollection;
use App\Modules\DocumentRejectionReasonMaster\Models\DocumentRejectionReasonMaster;
use App\Modules\JobCard\Models\JobCard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    Livewire::test(Edit::class)
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
