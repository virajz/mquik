<?php

use App\Modules\CustomerVehicleMaster\Exporters\CustomerVehicleExporter;
use App\Modules\CustomerVehicleMaster\Importers\CustomerVehicleImporter;

return [
    'label' => 'Customer Vehicles',
    'description' => 'Vehicles owned by customers — used by appointments, job cards, and insurance claims.',
    'group' => 'Customers',
    'icon' => 'truck',
    'permissions' => [
        'customer_vehicle_master.view',
        'customer_vehicle_master.create',
        'customer_vehicle_master.update',
        'customer_vehicle_master.delete',
        'customer_vehicle_master.export',
        'customer_vehicle_master.import',
    ],
    'exportable' => CustomerVehicleExporter::class,
    'importable' => CustomerVehicleImporter::class,
];
