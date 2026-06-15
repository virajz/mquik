<?php

use App\Modules\ServicePackageTypeMaster\Exporters\ServicePackageTypeExporter;
use App\Modules\ServicePackageTypeMaster\Importers\ServicePackageTypeImporter;

return [
    'label' => 'Service Package Types',
    'description' => 'Service package categories — periodic service, accident repair, combo, AMC.',
    'group' => 'Workshop',
    'icon' => 'gift',
    'permissions' => [
        'service_package_type_master.view',
        'service_package_type_master.create',
        'service_package_type_master.update',
        'service_package_type_master.delete',
        'service_package_type_master.export',
        'service_package_type_master.import',
    ],
    'exportable' => ServicePackageTypeExporter::class,
    'importable' => ServicePackageTypeImporter::class,
];
