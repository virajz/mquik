<?php

use App\Modules\SpareBrandMaster\Exporters\SpareBrandExporter;
use App\Modules\SpareBrandMaster\Importers\SpareBrandImporter;

return [
    'label' => 'Spare Brands',
    'description' => 'Brands used across spares, inquiries, and procurement.',
    'group' => 'Inventory',
    'icon' => 'tag',
    'permissions' => [
        'spare_brand_master.view',
        'spare_brand_master.create',
        'spare_brand_master.update',
        'spare_brand_master.delete',
        'spare_brand_master.export',
        'spare_brand_master.import',
    ],
    'exportable' => SpareBrandExporter::class,
    'importable' => SpareBrandImporter::class,
];
