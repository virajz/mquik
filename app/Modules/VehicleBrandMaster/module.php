<?php

use App\Modules\VehicleBrandMaster\Exporters\VehicleBrandExporter;
use App\Modules\VehicleBrandMaster\Importers\VehicleBrandImporter;

return [
    'label' => 'Vehicle Brands',
    'description' => 'Brands of vehicles serviced at this workshop.',
    'group' => 'Masters',
    'icon' => 'truck',
    'permissions' => [
        'vehicle_brand_master.view',
        'vehicle_brand_master.create',
        'vehicle_brand_master.update',
        'vehicle_brand_master.delete',
        'vehicle_brand_master.export',
        'vehicle_brand_master.import',
    ],
    'exportable' => VehicleBrandExporter::class,
    'importable' => VehicleBrandImporter::class,
];
