<?php

use App\Modules\ServiceTypeMaster\Exporters\ServiceTypeExporter;
use App\Modules\ServiceTypeMaster\Importers\ServiceTypeImporter;

return [
    'label' => 'Service Types',
    'description' => 'How the workshop categorises a job — drives advisor routing, packages, and reports.',
    'group' => 'Masters',
    'icon' => 'wrench-screwdriver',
    'permissions' => [
        'service_type_master.view',
        'service_type_master.create',
        'service_type_master.update',
        'service_type_master.delete',
        'service_type_master.export',
        'service_type_master.import',
    ],
    'exportable' => ServiceTypeExporter::class,
    'importable' => ServiceTypeImporter::class,
];
