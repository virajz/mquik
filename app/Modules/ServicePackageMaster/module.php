<?php

use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;

return [
    'label' => 'Service Packages',
    'description' => 'Combo and AMC service packages — bundled services with validity windows and reminders.',
    'group' => 'Workshop',
    'icon' => 'gift',
    'permissions' => [
        'service_package_master.view',
        'service_package_master.create',
        'service_package_master.update',
        'service_package_master.delete',
    ],
    'searchable' => [
        'model' => ServicePackageMaster::class,
        'route' => 'service-package-master.index',
    ],
];
