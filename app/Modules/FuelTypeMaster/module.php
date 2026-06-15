<?php

use App\Modules\FuelTypeMaster\Exporters\FuelTypeExporter;
use App\Modules\FuelTypeMaster\Importers\FuelTypeImporter;

return [
    'label' => 'Fuel Types',
    'description' => 'Vehicle fuel types — petrol, diesel, CNG, electric, hybrid.',
    'group' => 'Vehicles',
    'icon' => 'fire',
    'permissions' => [
        'fuel_type_master.view',
        'fuel_type_master.create',
        'fuel_type_master.update',
        'fuel_type_master.delete',
        'fuel_type_master.export',
        'fuel_type_master.import',
    ],
    'exportable' => FuelTypeExporter::class,
    'importable' => FuelTypeImporter::class,
];
