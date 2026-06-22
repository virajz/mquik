<?php

use App\Modules\BayMaster\Exporters\BayExporter;
use App\Modules\BayMaster\Importers\BayImporter;

return [
    'label' => 'Bays',
    'description' => 'Physical service bays and work stations on the workshop floor.',
    'group' => 'Workshop',
    'icon' => 'squares-2x2',
    'permissions' => [
        'bay_master.view',
        'bay_master.create',
        'bay_master.update',
        'bay_master.delete',
        'bay_master.export',
        'bay_master.import',
    ],
    'exportable' => BayExporter::class,
    'importable' => BayImporter::class,
];
