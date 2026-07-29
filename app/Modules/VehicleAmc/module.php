<?php

use App\Modules\VehicleAmc\Models\VehicleAmc;

return [
    'label' => 'Vehicle AMC',
    'description' => 'Manage AMC packages, included services / spares, usage limits, payment and expiry (MQ/AMC FY series).',
    'group' => 'Sales',
    'icon' => 'identification',
    'permissions' => [
        'vehicle_amc.view',
        'vehicle_amc.create',
        'vehicle_amc.update',
        'vehicle_amc.delete',
    ],
    'searchable' => [
        'model' => VehicleAmc::class,
        'route' => 'vehicle-amc.index',
    ],
];
