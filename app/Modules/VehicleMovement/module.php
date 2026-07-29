<?php

use App\Modules\VehicleMovement\Models\VehicleMovement;

return [
    'label' => 'Vehicle Inward / Outward',
    'description' => 'Gate log of vehicles entering or leaving — slot, gate, outward purpose, driver, number plate, entry/exit and TAT.',
    'group' => 'Workshop',
    'icon' => 'arrows-right-left',
    'permissions' => [
        'vehicle_movement.view',
        'vehicle_movement.create',
        'vehicle_movement.update',
        'vehicle_movement.delete',
    ],
    'searchable' => [
        'model' => VehicleMovement::class,
        'route' => 'vehicle-movement.index',
    ],
];
