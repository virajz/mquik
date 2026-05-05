<?php

use App\Modules\VehicleModelMaster\Exporters\VehicleModelExporter;
use App\Modules\VehicleModelMaster\Importers\VehicleModelImporter;

return [
    'label' => 'Vehicle Models',
    'description' => 'Models within each vehicle brand.',
    'group' => 'Masters',
    'icon' => 'cube',
    'permissions' => [
        'vehicle_model_master.view',
        'vehicle_model_master.create',
        'vehicle_model_master.update',
        'vehicle_model_master.delete',
        'vehicle_model_master.export',
        'vehicle_model_master.import',
    ],
    'exportable' => VehicleModelExporter::class,
    'importable' => VehicleModelImporter::class,
];
