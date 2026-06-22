<?php

use App\Modules\ReturnTypeMaster\Exporters\ReturnTypeExporter;
use App\Modules\ReturnTypeMaster\Importers\ReturnTypeImporter;

return [
    'label' => 'Return Types',
    'description' => 'Why issued parts are returned to the store.',
    'group' => 'Inventory',
    'icon' => 'arrow-uturn-left',
    'permissions' => [
        'return_type_master.view',
        'return_type_master.create',
        'return_type_master.update',
        'return_type_master.delete',
        'return_type_master.export',
        'return_type_master.import',
    ],
    'exportable' => ReturnTypeExporter::class,
    'importable' => ReturnTypeImporter::class,
];
