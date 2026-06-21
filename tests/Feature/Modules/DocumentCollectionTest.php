<?php

use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DocumentCollection\Livewire\Edit;
use App\Modules\DocumentCollection\Livewire\Index;
use App\Modules\DocumentCollection\Models\DocumentCollection;
use App\Modules\DocumentRejectionReasonMaster\Models\DocumentRejectionReasonMaster;
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
