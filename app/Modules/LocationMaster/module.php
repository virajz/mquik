<?php

use App\Modules\LocationMaster\Exporters\LocationExporter;
use App\Modules\LocationMaster\Importers\LocationImporter;

return [
    'label' => 'Locations',
    'description' => 'Workshop branches — used by Job Cards, Inventory, Invoicing for branch-level reporting.',
    'group' => 'Locations',
    'icon' => 'building-office-2',
    'permissions' => [
        'location_master.view',
        'location_master.create',
        'location_master.update',
        'location_master.delete',
        'location_master.export',
        'location_master.import',
    ],
    'exportable' => LocationExporter::class,
    'importable' => LocationImporter::class,
];
