<?php

use App\Modules\Appointment\Models\Appointment;

return [
    'label' => 'Appointments',
    'description' => 'Customer service bookings — channel, time slot, vehicle, advisor, optional pickup.',
    'group' => 'Workshop',
    'icon' => 'calendar-days',
    'permissions' => [
        'appointment.view',
        'appointment.create',
        'appointment.update',
        'appointment.delete',
    ],
    'searchable' => [
        'model' => Appointment::class,
        'route' => 'appointment.index',
    ],
];
