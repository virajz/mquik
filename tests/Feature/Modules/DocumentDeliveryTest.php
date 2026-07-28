<?php

use App\Models\User;
use App\Modules\DocumentDelivery\Livewire\Edit;
use App\Modules\DocumentDelivery\Livewire\Index;
use App\Modules\DocumentDelivery\Models\DocumentDelivery;
use App\Modules\DocumentDelivery\Models\DocumentDeliveryItem;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    DocumentDelivery::factory()->count(3)->create();

    $this->get(route('document-delivery.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('renders the create page with the standard checklist pre-seeded', function () {
    Livewire::test(Edit::class)
        ->assertCount('items', count(DocumentDelivery::standardDocuments()))
        ->assertSet('items.0.document_name', 'RC BOOK');
});

it('creates a delivery, stamps the number, and keeps only ticked checklist rows', function () {
    Livewire::test(Edit::class)
        ->set('recipient_type', 'owner_self')
        ->set('delivery_mode', 'hand_to_hand')
        ->set('items.0.is_delivered', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('document-delivery.index'));

    $d = DocumentDelivery::with('items')->first();
    expect($d->delivery_no)->toBe('DD-'.str_pad((string) $d->id, 5, '0', STR_PAD_LEFT))
        ->and($d->delivery_mode)->toBe('hand_to_hand')
        ->and($d->items->count())->toBe(count(DocumentDelivery::standardDocuments()))
        ->and($d->items->firstWhere('document_name', 'RC BOOK')->is_delivered)->toBeTrue();
});

it('requires a delivered-at timestamp when status is delivered', function () {
    Livewire::test(Edit::class)
        ->set('status', DocumentDelivery::STATUS_DELIVERED)
        ->call('save')
        ->assertHasErrors(['delivered_at']);
});

it('validates delivery mode and acknowledgement against the allowed lists', function () {
    Livewire::test(Edit::class)
        ->set('delivery_mode', 'nope')
        ->set('acknowledgement_type', 'nope')
        ->call('save')
        ->assertHasErrors(['delivery_mode', 'acknowledgement_type']);
});

it('strips blank checklist rows', function () {
    Livewire::test(Edit::class)
        ->set('items', [
            ['id' => null, 'document_name' => 'RC BOOK', 'is_delivered' => true, 'notes' => null],
            ['id' => null, 'document_name' => '', 'is_delivered' => false, 'notes' => null],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(DocumentDelivery::first()->items)->toHaveCount(1);
});

it('filters by status, mode and insurer', function () {
    $insurer = InsuranceCompanyMaster::factory()->create();
    $mine = DocumentDelivery::factory()->inTransit()->create(['insurance_company_id' => $insurer->id]);
    $other = DocumentDelivery::factory()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', DocumentDelivery::STATUS_IN_TRANSIT)
        ->assertSee($mine->delivery_no)
        ->assertDontSee($other->delivery_no);

    Livewire::test(Index::class)
        ->set('modeFilter', 'courier')
        ->assertSee($mine->delivery_no)
        ->assertDontSee($other->delivery_no);

    Livewire::test(Index::class)
        ->set('companyFilter', (string) $insurer->id)
        ->assertSee($mine->delivery_no)
        ->assertDontSee($other->delivery_no);
});

it('exports the document delivery report as CSV', function () {
    DocumentDelivery::factory()->delivered()->create();

    Livewire::test(Index::class)
        ->call('download')
        ->assertFileDownloaded();
});

it('counts by status', function () {
    DocumentDelivery::factory()->create();
    DocumentDelivery::factory()->inTransit()->create();
    DocumentDelivery::factory()->delivered()->create();
    DocumentDelivery::factory()->returned()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending'] === 1
            && $kpis['in_transit'] === 1
            && $kpis['delivered'] === 1
            && $kpis['returned'] === 1);
});

it('deletes a delivery and cascades its checklist', function () {
    $d = DocumentDelivery::factory()->create();
    $d->items()->create(['document_name' => 'RC BOOK', 'sequence_no' => 1]);

    Livewire::test(Index::class)->call('delete', $d->id);

    expect(DocumentDelivery::find($d->id))->toBeNull()
        ->and(DocumentDeliveryItem::where('document_delivery_id', $d->id)->count())->toBe(0);
});

it('blocks the create page without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('document_delivery.view');
    $this->actingAs($user);

    $this->get(route('document-delivery.create'))->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('document-delivery.index'))->assertRedirect(route('login'));
});
