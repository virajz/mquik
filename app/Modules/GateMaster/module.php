<?php

use App\Modules\GateMaster\Exporters\GateExporter;
use App\Modules\GateMaster\Importers\GateImporter;

return [
    'label' => 'Gates',
    'description' => 'Physical entry and exit gates the security desk records vehicles through.',
    'group' => 'Workshop',
    'icon' => 'building-office',
    'permissions' => [
        'gate_master.view',
        'gate_master.create',
        'gate_master.update',
        'gate_master.delete',
        'gate_master.export',
        'gate_master.import',
    ],
    'exportable' => GateExporter::class,
    'importable' => GateImporter::class,
];
