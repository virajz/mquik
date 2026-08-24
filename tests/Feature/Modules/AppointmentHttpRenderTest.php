<?php

use App\Modules\Appointment\Models\Appointment;

it('renders the appointment edit page over HTTP', function () {
    $this->actingAs(adminUser());
    $appointment = Appointment::factory()->create();

    $response = $this->get(route('appointment.edit', $appointment));

    $response->assertOk()
        ->assertSee('Customer Note')
        ->assertSee('Internal Notes')
        ->assertSee('Customer Complaints')
        ->assertSee('Appointment Date');
});

it('renders the appointment create page over HTTP', function () {
    $this->actingAs(adminUser());

    $this->get(route('appointment.create'))->assertOk()->assertSee('Customer Note');
});

it('renders the appointment index over HTTP', function () {
    $this->actingAs(adminUser());
    Appointment::factory()->count(2)->create();

    $this->get(route('appointment.index'))->assertOk()->assertSee('Pending Reason');
});
