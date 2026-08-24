<?php

use App\Modules\PickupDrop\Models\PickupDrop;

it('renders the pickup/drop index over HTTP', function () {
    $this->actingAs(adminUser());
    PickupDrop::factory()->count(2)->create();

    $this->get(route('pickup-drop.index'))->assertOk()->assertSee('Pending Reason')->assertSee('Job Card');
});

it('renders the pickup/drop create and edit pages over HTTP', function () {
    $this->actingAs(adminUser());

    $this->get(route('pickup-drop.create'))->assertOk()->assertSee('Document Collection');
    $this->get(route('pickup-drop.edit', PickupDrop::factory()->create()))->assertOk()->assertSee('Driver Progress');
});
