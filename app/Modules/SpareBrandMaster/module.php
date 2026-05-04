<?php

use App\Modules\SpareBrandMaster\Exporters\SpareBrandExporter;

return [
    'label' => 'Spare Brands',
    'description' => 'Brands used across spares, inquiries, and procurement.',
    'group' => 'Masters',
    'icon' => 'tag',
    'permissions' => [
        'spare_brand_master.view',
        'spare_brand_master.create',
        'spare_brand_master.update',
        'spare_brand_master.delete',
        'spare_brand_master.export',
    ],
    'exportable' => SpareBrandExporter::class,
];
