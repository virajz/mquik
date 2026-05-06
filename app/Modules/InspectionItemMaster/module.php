<?php

use App\Modules\InspectionItemMaster\Exporters\InspectionItemExporter;
use App\Modules\InspectionItemMaster\Importers\InspectionItemImporter;

return [
    'label' => 'Inspection Items',
    'description' => 'Individual checkpoints performed during inspections — Engine Oil Level, Tyre Tread Depth, Brake Pad Thickness, etc.',
    'group' => 'Inspection',
    'icon' => 'magnifying-glass-circle',
    'permissions' => [
        'inspection_item_master.view',
        'inspection_item_master.create',
        'inspection_item_master.update',
        'inspection_item_master.delete',
        'inspection_item_master.export',
        'inspection_item_master.import',
    ],
    'exportable' => InspectionItemExporter::class,
    'importable' => InspectionItemImporter::class,
];
