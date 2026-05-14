<?php

use App\Modules\LabourMaster\Exporters\LabourExporter;
use App\Modules\LabourMaster\Importers\LabourImporter;
use App\Modules\LabourMaster\Models\LabourMaster;

return [
    'label' => 'Labour',
    'description' => 'Labour catalog with HSN/SAC, segment, rate, OSL flag and department.',
    'group' => 'Inventory',
    'icon' => 'wrench-screwdriver',
    'permissions' => [
        'labour_master.view',
        'labour_master.create',
        'labour_master.update',
        'labour_master.delete',
        'labour_master.export',
        'labour_master.import',
    ],
    'exportable' => LabourExporter::class,
    'importable' => LabourImporter::class,
    'searchable' => [
        'model' => LabourMaster::class,
        'route' => 'labour-master.index',
    ],
];
