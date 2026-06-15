<?php

use App\Modules\PhotoTypeMaster\Exporters\PhotoTypeExporter;
use App\Modules\PhotoTypeMaster\Importers\PhotoTypeImporter;

return [
    'label' => 'Photo Types',
    'description' => 'Categories of job-card photos — odometer, damage close-up, engine bay, VIN plate, etc.',
    'group' => 'Workshop',
    'icon' => 'camera',
    'permissions' => [
        'photo_type_master.view',
        'photo_type_master.create',
        'photo_type_master.update',
        'photo_type_master.delete',
        'photo_type_master.export',
        'photo_type_master.import',
    ],
    'exportable' => PhotoTypeExporter::class,
    'importable' => PhotoTypeImporter::class,
];
