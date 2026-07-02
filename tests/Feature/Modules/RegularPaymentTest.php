<?php

use App\Modules\PurchaseEntry\Models\PurchaseEntry;
use App\Modules\RegularPayment\Livewire\Edit;
use App\Modules\RegularPayment\Livewire\Index;
use App\Modules\RegularPayment\Models\RegularPayment;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Support\FinancialYear;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    RegularPayment::factory()->count(2)->create();

    $this->get(route('regular-payment.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('regular-payment.index'))->assertRedirect(route('login'));
});

it('creates a payment, stamps the MQ/PV FY number, and redirects', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('amount', 3200)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $p = RegularPayment::firstOrFail();
    $fy = FinancialYear::label();
    expect($p->payment_no)->toBe('MQ/PV/'.$fy.'/00001')
        ->and($p->fy_label)->toBe($fy)
        ->and((float) $p->amount)->toBe(3200.0);
});

it('prefills vendor and amount from a purchase entry', function () {
    $vendor = VendorMaster::factory()->create();
    $pe = PurchaseEntry::factory()->create(['vendor_id' => $vendor->id, 'grand_total' => 2000]);

    $component = Livewire::withQueryParams(['from-purchase-entry' => $pe->id])->test(Edit::class);

    expect($component->get('purchase_entry_id'))->toBe($pe->id)
        ->and($component->get('vendor_id'))->toBe($vendor->id)
        ->and((float) $component->get('amount'))->toBe(2000.0);
});

it('stamps paid_at when marked paid', function () {
    $p = RegularPayment::factory()->create();

    Livewire::test(Edit::class, ['regularPayment' => $p])
        ->set('status', 'paid')
        ->call('save')
        ->assertHasNoErrors();

    expect($p->refresh()->paid_at)->not->toBeNull();
});

it('stores an attachment tagged with its type', function () {
    Storage::fake('public');
    $p = RegularPayment::factory()->create();

    Livewire::test(Edit::class, ['regularPayment' => $p])
        ->set('attachmentType', 'cheque_copy')
        ->set('attachmentFiles.0', UploadedFile::fake()->image('cheque.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $att = $p->refresh()->attachments->first();
    expect($att)->not->toBeNull()
        ->and($att->attachment_type)->toBe('cheque_copy');
    Storage::disk('public')->assertExists($att->path);
});

it('deletes a payment from the index', function () {
    $p = RegularPayment::factory()->create();

    Livewire::test(Index::class)->call('delete', $p->id);

    expect(RegularPayment::find($p->id))->toBeNull();
});
