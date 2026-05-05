<?php

use App\Modules\VehicleVariantMaster\Exporters\VehicleVariantExporter;
use App\Modules\VehicleVariantMaster\Importers\VehicleVariantImporter;

return [
    'label' => 'Vehicle Variants',
    'description' => 'Trim levels within each vehicle model.',
    'group' => 'Masters',
    'icon' => 'adjustments-horizontal',
    'permissions' => [
        'vehicle_variant_master.view',
        'vehicle_variant_master.create',
        'vehicle_variant_master.update',
        'vehicle_variant_master.delete',
        'vehicle_variant_master.export',
        'vehicle_variant_master.import',
    ],
    'exportable' => VehicleVariantExporter::class,
    'importable' => VehicleVariantImporter::class,
];
