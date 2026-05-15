<?php

use App\Models\User;
use App\Modules\InternalPartsInquiry\Livewire\Index;
use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    InternalPartsInquiry::factory()->count(3)->create();

    $this->get(route('internal-parts-inquiry.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters records by search on ipi_no', function () {
    $a = InternalPartsInquiry::factory()->create();
    $b = InternalPartsInquiry::factory()->create();

    $a->forceFill(['ipi_no' => 'IPI-00001'])->saveQuietly();
    $b->forceFill(['ipi_no' => 'IPI-00002'])->saveQuietly();

    Livewire::test(Index::class)
        ->set('search', 'IPI-00001')
        ->assertSee('IPI-00001')
        ->assertDontSee('IPI-00002');
});

it('filters by status', function () {
    $open = InternalPartsInquiry::factory()->create();
    $closed = InternalPartsInquiry::factory()->closed()->create();

    $open->forceFill(['ipi_no' => 'IPI-OPEN1'])->saveQuietly();
    $closed->forceFill(['ipi_no' => 'IPI-CLSD1'])->saveQuietly();

    Livewire::test(Index::class)
        ->set('statusFilter', 'open')
        ->assertSee('IPI-OPEN1')
        ->assertDontSee('IPI-CLSD1');
});

it('deletes a record from the index', function () {
    $record = InternalPartsInquiry::factory()->create();

    Livewire::test(Index::class)
        ->call('delete', $record->id);

    expect(InternalPartsInquiry::find($record->id))->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('internal-parts-inquiry.index'))->assertRedirect(route('login'));
});

it('denies access without permission', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('internal-parts-inquiry.index'))->assertForbidden();
});
