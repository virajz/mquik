<?php

use App\Modules\InternalPartOrder\Livewire\Edit;
use App\Modules\InternalPartOrder\Livewire\Index;
use App\Modules\InternalPartOrder\Models\InternalPartOrder;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    InternalPartOrder::factory()->count(2)->create();

    $this->get(route('internal-part-order.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('internal-part-order.index'))->assertRedirect(route('login'));
});

it('creates an IPO, stamps the number, and redirects into the editor', function () {
    $jobCard = JobCard::factory()->create();

    Livewire::test(Edit::class)
        ->set('job_card_id', $jobCard->id)
        ->set('ipo_type', 'general_use')
        ->set('order_priority', 'urgent')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $ipo = InternalPartOrder::firstOrFail();
    expect($ipo->order_no)->toBe('IPO-'.str_pad((string) $ipo->id, 5, '0', STR_PAD_LEFT))
        ->and($ipo->ipo_type)->toBe('general_use')
        ->and($ipo->order_priority)->toBe('urgent');
});

it('prefills description, uom and stock status when a spare is picked', function () {
    $uom = UnitOfMeasureMaster::factory()->create();
    $spare = SpareMaster::factory()->create(['name' => 'BRAKE PAD SET', 'uom_id' => $uom->id]);
    $ipo = InternalPartOrder::factory()->create();

    $component = Livewire::test(Edit::class, ['internalPartOrder' => $ipo])
        ->call('addItem')
        ->set('items.0.spare_id', $spare->id);

    expect($component->get('items')[0]['description'])->toBe('BRAKE PAD SET')
        ->and($component->get('items')[0]['uom_id'])->toBe($uom->id)
        ->and($component->get('items')[0]['stock_status'])->toBe('out_of_stock'); // no stock entries
});

it('saves part lines with issue and stock status', function () {
    $ipo = InternalPartOrder::factory()->create();

    Livewire::test(Edit::class, ['internalPartOrder' => $ipo])
        ->set('status', 'partially_issued')
        ->set('items', [
            ['id' => null, 'spare_id' => null, 'uom_id' => null, 'return_type_id' => null, 'description' => 'brake pad', 'is_alternate' => false, 'qty_requested' => 4, 'qty_issued' => 2, 'qty_returned' => 0, 'stock_status' => 'available', 'issue_status' => 'partially_issued', 'return_status' => null, 'before_photo_path' => null, 'after_photo_path' => null, 'notes' => null, 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $ipo->refresh()->load('items');
    expect($ipo->status)->toBe('partially_issued')
        ->and($ipo->issued_at)->not->toBeNull()
        ->and($ipo->items)->toHaveCount(1)
        ->and($ipo->items[0]->description)->toBe('BRAKE PAD')
        ->and((float) $ipo->items[0]->qty_issued)->toBe(2.0)
        ->and($ipo->items[0]->issue_status)->toBe('partially_issued');
});

it('stamps approved_at when approval status becomes approved', function () {
    $ipo = InternalPartOrder::factory()->create();

    Livewire::test(Edit::class, ['internalPartOrder' => $ipo])
        ->set('approval_status', 'approved')
        ->call('save')
        ->assertHasNoErrors();

    expect($ipo->fresh()->approved_at)->not->toBeNull();
});

it('deletes an IPO from the index', function () {
    $ipo = InternalPartOrder::factory()->create();

    Livewire::test(Index::class)->call('delete', $ipo->id);

    expect(InternalPartOrder::find($ipo->id))->toBeNull();
});
