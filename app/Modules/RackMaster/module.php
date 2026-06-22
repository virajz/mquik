<?php

use App\Modules\RackMaster\Exporters\RackExporter;
use App\Modules\RackMaster\Importers\RackImporter;

return [
    'label' => 'Racks',
    'description' => 'Physical storage racks and bins in the parts store.',
    'group' => 'Inventory',
    'icon' => 'squares-2x2',
    'permissions' => [
        'rack_master.view',
        'rack_master.create',
        'rack_master.update',
        'rack_master.delete',
        'rack_master.export',
        'rack_master.import',
    ],
    'exportable' => RackExporter::class,
    'importable' => RackImporter::class,
];
