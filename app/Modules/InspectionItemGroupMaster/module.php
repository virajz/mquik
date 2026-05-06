<?php

use App\Modules\InspectionItemGroupMaster\Exporters\InspectionItemGroupExporter;
use App\Modules\InspectionItemGroupMaster\Importers\InspectionItemGroupImporter;

return [
    'label' => 'Inspection Item Groups',
    'description' => 'Categories of inspection items — Engine, Brake, Suspension, Body, Tyre, etc.',
    'group' => 'Inspection',
    'icon' => 'clipboard-document-check',
    'permissions' => [
        'inspection_item_group_master.view',
        'inspection_item_group_master.create',
        'inspection_item_group_master.update',
        'inspection_item_group_master.delete',
        'inspection_item_group_master.export',
        'inspection_item_group_master.import',
    ],
    'exportable' => InspectionItemGroupExporter::class,
    'importable' => InspectionItemGroupImporter::class,
];
