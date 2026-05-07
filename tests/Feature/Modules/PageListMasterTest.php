<?php

use App\Models\User;
use App\Modules\PageListMaster\Livewire\Index;
use Livewire\Livewire;

it('renders the index page for an admin', function () {
    $this->actingAs(adminUser());

    $this->get(route('page-list-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSee('Page List');
});

it('returns 403 for a user without page_list_master.view permission', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('page-list-master.index'))->assertForbidden();
});

it('lists every registered module — at least Customers and Vendors are present', function () {
    $this->actingAs(adminUser());

    Livewire::test(Index::class)
        ->assertSee('CustomerMaster')
        ->assertSee('VendorMaster');
});

it('filters by group', function () {
    $this->actingAs(adminUser());

    Livewire::test(Index::class)
        ->set('groupFilter', 'Customers')
        ->assertSee('CustomerMaster')
        ->assertDontSee('VendorMaster');
});

it('filters by search term', function () {
    $this->actingAs(adminUser());

    Livewire::test(Index::class)
        ->set('search', 'vendor')
        ->assertSee('VendorMaster')
        ->assertDontSee('CustomerMaster');
});
