<?php

use App\Modules\RegionMaster\Exporters\RegionExporter;
use App\Modules\RegionMaster\Importers\RegionImporter;

return [
    'label' => 'Regions',
    'description' => 'Geographic hierarchy used by Customer, Vendor, and Employee addresses.',
    'group' => 'Locations',
    'icon' => 'map-pin',
    'permissions' => [
        'region_master.view',
        'region_master.create',
        'region_master.update',
        'region_master.delete',
        'region_master.export',
        'region_master.import',
    ],
    'exportable' => RegionExporter::class,
    'importable' => RegionImporter::class,
];
