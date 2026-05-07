<?php

use App\Modules\BusinessTypeMaster\Exporters\BusinessTypeExporter;
use App\Modules\BusinessTypeMaster\Importers\BusinessTypeImporter;

return [
    'label' => 'Business Types',
    'description' => 'Customer business types — used for segmentation, pricing, and credit terms.',
    'group' => 'Customers',
    'icon' => 'briefcase',
    'permissions' => [
        'business_type_master.view',
        'business_type_master.create',
        'business_type_master.update',
        'business_type_master.delete',
        'business_type_master.export',
        'business_type_master.import',
    ],
    'exportable' => BusinessTypeExporter::class,
    'importable' => BusinessTypeImporter::class,
];
