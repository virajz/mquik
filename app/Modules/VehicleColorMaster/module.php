<?php

use App\Modules\VehicleColorMaster\Exporters\VehicleColorExporter;
use App\Modules\VehicleColorMaster\Importers\VehicleColorImporter;

return [
    'label' => 'Vehicle Colors',
    'description' => 'Paint colors used on customer vehicles.',
    'group' => 'Masters',
    'icon' => 'swatch',
    'permissions' => [
        'vehicle_color_master.view',
        'vehicle_color_master.create',
        'vehicle_color_master.update',
        'vehicle_color_master.delete',
        'vehicle_color_master.export',
        'vehicle_color_master.import',
    ],
    'exportable' => VehicleColorExporter::class,
    'importable' => VehicleColorImporter::class,
];
