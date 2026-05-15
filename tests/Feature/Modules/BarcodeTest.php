<?php

use App\Models\User;
use App\Modules\Barcode\Livewire\Index;
use App\Modules\Barcode\Models\BarcodeLabel;
use App\Modules\Barcode\Services\BarcodeService;
use App\Modules\SpareMaster\Models\SpareMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the barcode index page', function () {
    $this->get(route('barcode.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('barcode.index'))->assertRedirect(route('login'));
});

it('denies access without permission', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('barcode.index'))->assertForbidden();
});

it('BarcodeService::generate produces a deterministic code for a spare ID', function () {
    $code = BarcodeService::generate(42);

    expect($code)->toStartWith('MQ0000042')
        ->and(strlen($code))->toBe(10);
});

it('BarcodeService::generate produces different codes for different spare IDs', function () {
    expect(BarcodeService::generate(1))->not->toBe(BarcodeService::generate(2));
});

it('BarcodeService::ensurePrimary creates a primary label if none exists', function () {
    $spare = SpareMaster::factory()->create();

    $label = BarcodeService::ensurePrimary($spare);

    expect($label->spare_id)->toBe($spare->id)
        ->and($label->is_primary)->toBeTrue()
        ->and($label->barcode)->toStartWith('MQ');
});

it('BarcodeService::ensurePrimary returns existing primary without creating a duplicate', function () {
    $spare = SpareMaster::factory()->create();

    $first = BarcodeService::ensurePrimary($spare);
    $second = BarcodeService::ensurePrimary($spare);

    expect($second->id)->toBe($first->id)
        ->and(BarcodeLabel::where('spare_id', $spare->id)->count())->toBe(1);
});

it('BarcodeService::resolve returns the spare for a known barcode', function () {
    $spare = SpareMaster::factory()->create();
    $label = BarcodeLabelFactory($spare);

    $resolved = BarcodeService::resolve($label->barcode);

    expect($resolved?->id)->toBe($spare->id);
});

it('BarcodeService::resolve returns null for unknown barcode', function () {
    expect(BarcodeService::resolve('UNKNOWN-BARCODE'))->toBeNull();
});

it('Index generates a barcode label via modal', function () {
    $spare = SpareMaster::factory()->create();

    Livewire::test(Index::class)
        ->set('selectedSpareId', $spare->id)
        ->set('barcodeType', BarcodeLabel::TYPE_CODE128)
        ->set('labelSize', '50x25')
        ->set('copies', 2)
        ->call('generate')
        ->assertHasNoErrors();

    expect(BarcodeLabel::where('spare_id', $spare->id)->count())->toBeGreaterThanOrEqual(1);
});

it('Index requires a spare to be selected before generating', function () {
    Livewire::test(Index::class)
        ->set('selectedSpareId', null)
        ->call('generate')
        ->assertHasErrors(['selectedSpareId']);
});

it('Index deletes a barcode label', function () {
    $label = BarcodeLabel::factory()->create();

    Livewire::test(Index::class)
        ->call('delete', $label->id);

    expect(BarcodeLabel::find($label->id))->toBeNull();
});

it('Index filters labels by spare name search', function () {
    $s1 = SpareMaster::factory()->create(['name' => 'OIL FILTER']);
    $s2 = SpareMaster::factory()->create(['name' => 'BRAKE PAD']);

    BarcodeLabel::factory()->create(['spare_id' => $s1->id, 'barcode' => 'MQ0000001X']);
    BarcodeLabel::factory()->create(['spare_id' => $s2->id, 'barcode' => 'MQ0000002Y']);

    Livewire::test(Index::class)
        ->set('search', 'OIL')
        ->assertSee('MQ0000001X')
        ->assertDontSee('MQ0000002Y');
});

// Helper: create a BarcodeLabel for a spare using the service-generated code
function BarcodeLabelFactory(SpareMaster $spare): BarcodeLabel
{
    return BarcodeLabel::create([
        'spare_id' => $spare->id,
        'barcode' => BarcodeService::generate($spare->id),
        'barcode_type' => BarcodeLabel::TYPE_CODE128,
        'label_size' => '50x25',
        'copies' => 1,
        'is_primary' => true,
    ]);
}
