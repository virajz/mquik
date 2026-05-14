<?php

use App\Modules\SpareMaster\Exporters\SpareExporter;
use App\Modules\SpareMaster\Importers\SpareImporter;
use App\Modules\SpareMaster\Models\SpareMaster;

return [
    'label' => 'Spares',
    'description' => 'Spare parts catalog with HSN, brand, inventory grouping, MIN/MAX, UOM, barcode and tyre fields.',
    'group' => 'Inventory',
    'icon' => 'cube',
    'permissions' => [
        'spare_master.view',
        'spare_master.create',
        'spare_master.update',
        'spare_master.delete',
        'spare_master.export',
        'spare_master.import',
    ],
    'exportable' => SpareExporter::class,
    'importable' => SpareImporter::class,
    'searchable' => [
        'model' => SpareMaster::class,
        'route' => 'spare-master.index',
    ],
];
