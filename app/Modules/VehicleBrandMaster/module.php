<?php

use App\Modules\VehicleBrandMaster\Exporters\VehicleBrandExporter;
use App\Modules\VehicleBrandMaster\Importers\VehicleBrandImporter;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;

return [
    'label' => 'Vehicle Brands',
    'description' => 'Brands of vehicles serviced at this workshop.',
    'group' => 'Vehicles',
    'icon' => 'building-storefront',
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
    'searchable' => [
        'model' => VehicleBrandMaster::class,
        'route' => 'vehicle-brand-master.index',
    ],
];
